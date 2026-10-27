<?php

namespace App\Console\Commands;

use App\Exceptions\AttendanceImportException;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class SyncETimeOfficePunchData extends Command
{
    protected $signature = 'etimeoffice:sync-punches
        {--empcode= : Optional employee code for testing}
        {--from= : Start date and time in d/m/Y_H:i format}
        {--to= : End date and time in d/m/Y_H:i format}';

    protected $description = 'Sync detailed attendance punches and breaks from eTimeOffice';

    public function handle(AttendanceService $attendanceService): int
    {
        @set_time_limit(0);

        try {
            /*
             * DownloadPunchData does not return today's data.
             * Default to the previous complete day.
             */
            $defaultDate = now()->subDay();

            $fromDate = $this->parseDate(
                $this->option('from')
                    ?: $defaultDate->copy()
                    ->startOfDay()
                    ->format('d/m/Y_H:i')
            );

            $toDate = $this->parseDate(
                $this->option('to')
                    ?: $defaultDate->copy()
                    ->endOfDay()
                    ->format('d/m/Y_H:i')
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($fromDate->gt($toDate)) {
            $this->error(
                'The start date and time must be before or equal to the end date and time.'
            );

            return self::FAILURE;
        }

        $config = config('services.etimeoffice', []);

        foreach ([
                     'base_url',
                     'corporate_id',
                     'username',
                     'password',
                 ] as $key) {
            if (blank($config[$key] ?? null)) {
                $this->error(
                    "Missing eTimeOffice configuration: {$key}."
                );

                return self::FAILURE;
            }
        }

        $employeeCode = $this->option('empcode') ?: 'ALL';

        $parameters = [
            'FromDate' => $fromDate->format('d/m/Y_H:i'),
            'ToDate' => $toDate->format('d/m/Y_H:i'),
            'Empcode' => $employeeCode,
        ];

        $this->info('Starting eTimeOffice detailed punch sync...');
        $this->line("Employee: {$employeeCode}");
        $this->line("From: {$parameters['FromDate']}");
        $this->line("To: {$parameters['ToDate']}");

        $credentials = implode(':', [
            $config['corporate_id'],
            $config['username'],
            $config['password'],
            'true',
        ]);

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Authorization' => 'Basic '.base64_encode($credentials),
                ])
                ->connectTimeout(10)
                ->timeout(60)
                ->retry(3, 1000)
                ->get(
                    rtrim($config['base_url'], '/').'/DownloadPunchData',
                    $parameters,
                );

            $response->throw();

            $data = $response->json();

            if (
                ! is_array($data)
                || ($data['Error'] ?? true) !== false
                || ! isset($data['PunchData'])
                || ! is_array($data['PunchData'])
            ) {
                $this->error(
                    'The eTimeOffice API returned an unsuccessful or unexpected response.'
                );

                return self::FAILURE;
            }
        } catch (RequestException $exception) {
            report($exception);

            $this->error(
                'eTimeOffice API request failed with HTTP '.
                $exception->response->status().'.'
            );

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);

            $this->error(
                'Unable to fetch eTimeOffice punch records. Check the application logs.'
            );

            return self::FAILURE;
        }

        $this->info(
            'Fetched '.count($data['PunchData']).' raw punch record(s).'
        );

        $attendanceRecords = $this->normalizePunchRecords(
            $data['PunchData'],
        );

        if ($attendanceRecords === []) {
            $this->info('No valid attendance records found.');

            return self::SUCCESS;
        }

        $employeeCodes = collect($attendanceRecords)
            ->pluck('employee_code')
            ->filter()
            ->unique()
            ->values();

        $usersByEmployeeCode = User::query()
            ->whereIn('employee_code', $employeeCodes)
            ->get()
            ->keyBy('employee_code');

        $userIds = $usersByEmployeeCode
            ->pluck('id')
            ->values();

        /*
         * Load existing attendance records so detailed punches can
         * enrich records created by the hourly attendance sync.
         */
        $existingAttendance = Attendance::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('attendance_date', [
                $fromDate->toDateString(),
                $toDate->toDateString(),
            ])
            ->get()
            ->mapWithKeys(
                fn (Attendance $attendance) => [
                    $this->attendanceKey(
                        $attendance->user_id,
                        $attendance->attendance_date,
                    ) => $attendance,
                ]
            );

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $this->info(
            'Processing '.count($attendanceRecords).' attendance record(s)...'
        );

        foreach ($attendanceRecords as $record) {
            $employeeCode = $record['employee_code'];
            $attendanceDate = $record['attendance_date'];

            /** @var User|null $user */
            $user = $usersByEmployeeCode->get($employeeCode);

            if (! $user) {
                $this->warn("Employee not found: {$employeeCode}");

                $skipped++;

                continue;
            }

            $key = $this->attendanceKey(
                (int) $user->id,
                $attendanceDate,
            );

            /** @var Attendance|null $attendance */
            $attendance = $existingAttendance->get($key);

            try {
                if (! $attendance) {
                    $attendance = $attendanceService->import(
                        user: $user,
                        attendanceDate: $attendanceDate,
                        startAt: $record['start_at'],
                        endAt: $record['end_at'],
                        breaks: $record['breaks'],
                    );

                    $existingAttendance->put($key, $attendance);

                    $created++;

                    $this->line(
                        "Created {$employeeCode} on {$attendanceDate}; ".
                        'status: '.$this->attendanceStatus($record['end_at']).'; '.
                        'breaks: '.count($record['breaks']).'; '.
                        'total break: '.$this->formatDuration(
                            $record['break_seconds'],
                        )
                    );

                    continue;
                }

                /*
                 * Update existing attendance with detailed punches.
                 *
                 * When the punch count is odd, end_at is deliberately
                 * set to null because the employee has not clocked out.
                 */
                $attendance = $attendanceService->update(
                    $attendance,
                    [
                        'start_at' => $record['start_at'],
                        'end_at' => $record['end_at'],
                    ],
                    $record['breaks'],
                );

                $existingAttendance->put($key, $attendance);

                $updated++;

                $this->line(
                    "Updated {$employeeCode} on {$attendanceDate}; ".
                    'status: '.$this->attendanceStatus($record['end_at']).'; '.
                    'breaks: '.count($record['breaks']).'; '.
                    'total break: '.$this->formatDuration(
                        $record['break_seconds'],
                    )
                );
            } catch (AttendanceImportException $exception) {
                $this->warn(
                    "Skipped {$employeeCode} on {$attendanceDate}: ".
                    $exception->getMessage()
                );

                $skipped++;
            } catch (Throwable $exception) {
                report($exception);

                $this->error(
                    "Failed to sync {$employeeCode} on {$attendanceDate}: ".
                    $exception->getMessage()
                );

                $failed++;
            }
        }

        $this->newLine();
        $this->info("Attendance created: {$created}");
        $this->info("Attendance updated: {$updated}");
        $this->info("Attendance skipped: {$skipped}");
        $this->info("Attendance failed: {$failed}");

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Convert individual punches into attendance records.
     *
     * Punch interpretation:
     *
     * 1 punch: IN
     * 2 punches: IN, OUT
     * 3 punches: IN, break start, break end
     * 4 punches: IN, break start, break end, OUT
     * 5 punches: IN, break start, break end, break start, break end
     * 6 punches: IN, break start, break end, break start, break end, OUT
     *
     * Odd counts mean attendance is still open.
     * Even counts mean attendance has an OUT punch.
     *
     * @param  array<int, mixed>  $punches
     * @return array<int, array{
     *     employee_code: string,
     *     attendance_date: string,
     *     start_at: Carbon,
     *     end_at: Carbon|null,
     *     breaks: array<int, array{
     *         start_at: Carbon,
     *         end_at: Carbon,
     *         duration_seconds: int
     *     }>,
     *     break_seconds: int
     * }>
     */
    private function normalizePunchRecords(array $punches): array
    {
        $groups = [];

        foreach ($punches as $punch) {
            if (! is_array($punch)) {
                continue;
            }

            $employeeCode = trim(
                (string) ($punch['Empcode'] ?? '')
            );

            $punchDate = trim(
                (string) ($punch['PunchDate'] ?? '')
            );

            if ($employeeCode === '' || $punchDate === '') {
                continue;
            }

            try {
                $timestamp = Carbon::createFromFormat(
                    '!d/m/Y H:i:s',
                    $punchDate,
                );
            } catch (Throwable) {
                continue;
            }

            if (
                ! $timestamp
                || $timestamp->format('d/m/Y H:i:s') !== $punchDate
            ) {
                continue;
            }

            $attendanceDate = $timestamp->toDateString();
            $key = $employeeCode.':'.$attendanceDate;

            $groups[$key] ??= [
                'employee_code' => $employeeCode,
                'attendance_date' => $attendanceDate,
                'punches' => [],
            ];

            $groups[$key]['punches'][] = $timestamp;
        }

        $records = [];

        foreach ($groups as $group) {
            /*
             * Remove duplicate timestamps and sort chronologically.
             * API response order is not assumed to be chronological.
             */
            $timestamps = collect($group['punches'])
                ->unique(
                    fn (Carbon $timestamp) => $timestamp->timestamp
                )
                ->sortBy(
                    fn (Carbon $timestamp) => $timestamp->timestamp
                )
                ->values();

            $count = $timestamps->count();

            /*
             * Zero punches cannot form an attendance record.
             * A single punch is valid and represents an open attendance.
             */
            if ($count === 0) {
                continue;
            }

            $startAt = $timestamps->first();

            if (! $startAt) {
                continue;
            }

            /*
             * An even punch count ends with OUT.
             * An odd punch count ends with IN, so end_at remains null.
             */
            $endAt = $count % 2 === 0
                ? $timestamps->last()
                : null;

            if (
                $endAt !== null
                && $endAt->lessThanOrEqualTo($startAt)
            ) {
                $this->warn(
                    'Invalid attendance sequence for '.
                    "{$group['employee_code']} on ".
                    "{$group['attendance_date']}."
                );

                continue;
            }

            $breaks = [];
            $breakSeconds = 0;

            /*
             * The first punch is IN.
             *
             * Every pair after the first punch is a break start/end.
             * If the count is even, the final punch is OUT and is
             * therefore excluded from break pairing.
             *
             * If the count is odd, the final punch completes a break
             * and is included in the last pair.
             */
            $lastBreakPairIndex = $count - 1;

            for (
                $index = 1;
                $index < $lastBreakPairIndex;
                $index += 2
            ) {
                $breakStart = $timestamps->get($index);
                $breakEnd = $timestamps->get($index + 1);

                if (
                    ! $breakStart
                    || ! $breakEnd
                    || $breakEnd->lessThanOrEqualTo($breakStart)
                ) {
                    $this->warn(
                        'Invalid break sequence for '.
                        "{$group['employee_code']} on ".
                        "{$group['attendance_date']}."
                    );

                    continue 2;
                }

                $durationSeconds = (int) $breakStart->diffInSeconds(
                    $breakEnd,
                );

                $breaks[] = [
                    'start_at' => $breakStart,
                    'end_at' => $breakEnd,
                    'duration_seconds' => $durationSeconds,
                ];

                $breakSeconds += $durationSeconds;
            }

            $records[] = [
                'employee_code' => $group['employee_code'],
                'attendance_date' => $group['attendance_date'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'breaks' => $breaks,
                'break_seconds' => $breakSeconds,
            ];
        }

        return $records;
    }

    private function attendanceKey(
        int $userId,
        string|Carbon $attendanceDate,
    ): string {
        $date = $attendanceDate instanceof Carbon
            ? $attendanceDate->toDateString()
            : Carbon::parse($attendanceDate)->toDateString();

        return "{$userId}:{$date}";
    }

    private function parseDate(string $date): Carbon
    {
        try {
            $parsedDate = Carbon::createFromFormat(
                '!d/m/Y_H:i',
                $date,
            );
        } catch (Throwable) {
            $parsedDate = false;
        }

        if (
            ! $parsedDate
            || $parsedDate->format('d/m/Y_H:i') !== $date
        ) {
            throw new InvalidArgumentException(
                "Invalid date/time '{$date}'. Expected format: d/m/Y_H:i."
            );
        }

        return $parsedDate;
    }

    private function attendanceStatus(?Carbon $endAt): string
    {
        return $endAt === null ? 'Open' : 'Completed';
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $remainingSeconds,
        );
    }
}
