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
             *
             * Therefore the default is the previous complete day.
             *
             * Example:
             * If command runs on 02/09/2026 at 00:30,
             * it requests 01/09/2026 00:00 → 23:59.
             */
            $defaultDate = now()->subDay();

            $fromDate = $this->parseDate(
                $this->option('from')
                    ?: $defaultDate
                        ->copy()
                        ->startOfDay()
                        ->format('d/m/Y_H:i')
            );

            $toDate = $this->parseDate(
                $this->option('to')
                    ?: $defaultDate
                        ->copy()
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
                    rtrim($config['base_url'], '/').
                    '/DownloadPunchData',
                    $parameters,
                );

            $response->throw();

            $data = $response->json();

            if (
                ! is_array($data) ||
                ($data['Error'] ?? true) !== false ||
                ! isset($data['PunchData']) ||
                ! is_array($data['PunchData'])
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
                'Unable to fetch eTimeOffice punch records. '.
                'Check the application logs.'
            );

            return self::FAILURE;
        }

        $punchCount = count($data['PunchData']);

        $this->info(
            "Fetched {$punchCount} raw punch record(s)."
        );

        $attendanceRecords = $this->normalizePunchRecords(
            $data['PunchData'],
        );

        if ($attendanceRecords === []) {
            $this->info(
                'No complete attendance records found.'
            );

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
         * Load full Attendance models because an existing attendance
         * must be enriched with the detailed punch information.
         */
        $existingAttendance = Attendance::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('attendance_date', [
                $fromDate->format('Y-m-d'),
                $toDate->format('Y-m-d'),
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
            'Processing '.
            count($attendanceRecords).
            ' attendance record(s)...'
        );

        foreach ($attendanceRecords as $record) {
            $employeeCode = $record['employee_code'];

            /** @var User|null $user */
            $user = $usersByEmployeeCode->get($employeeCode);

            if (! $user) {
                $this->warn(
                    "Employee not found: {$employeeCode}"
                );

                $skipped++;

                continue;
            }

            $attendanceDate = $record['attendance_date'];

            $key = $this->attendanceKey(
                $user->id,
                $attendanceDate,
            );

            /** @var Attendance|null $attendance */
            $attendance = $existingAttendance->get($key);

            try {
                if (! $attendance) {
                    /*
                     * If hourly sync has not created the attendance yet,
                     * detailed punch sync creates it directly with breaks.
                     */
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
                        'breaks: '.count($record['breaks']).'; '.
                        'total break: '.
                        gmdate(
                            'H:i:s',
                            $record['break_seconds'],
                        )
                    );

                    continue;
                }

                /*
                 * Existing attendance was most likely created by the
                 * hourly DownloadInOutPunchData synchronization.
                 *
                 * Enrich it with the authoritative individual punches.
                 */
                $attendanceService->update(
                    $attendance,
                    [
                        'start_at' => $record['start_at'],
                        'end_at' => $record['end_at'],
                    ],
                    $record['breaks'],
                );

                $updated++;

                $this->line(
                    "Updated {$employeeCode} on {$attendanceDate}; ".
                    'breaks: '.count($record['breaks']).'; '.
                    'total break: '.
                    gmdate(
                        'H:i:s',
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
                    "Failed to sync {$employeeCode} on ".
                    "{$attendanceDate}."
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
     * Convert individual eTimeOffice punches into attendance records.
     *
     * Example:
     *
     * 08:43
     * 13:00
     * 13:56
     * 18:02
     *
     * becomes:
     *
     * IN     = 08:43
     * Break  = 13:00 → 13:56
     * OUT    = 18:02
     *
     * @param  array<int, mixed>  $punches
     * @return array<int, array{
     *     employee_code: string,
     *     attendance_date: string,
     *     start_at: Carbon,
     *     end_at: Carbon,
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
                ! $timestamp ||
                $timestamp->format('d/m/Y H:i:s') !== $punchDate
            ) {
                continue;
            }

            $attendanceDate = $timestamp->format('Y-m-d');

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
             * Every attendance day must have:
             *
             * IN
             * OUT
             *
             * and every intermediate pair represents a break.
             *
             * Therefore the number of punches must be even.
             */
            if ($count < 2 || $count % 2 !== 0) {
                $this->warn(
                    'Incomplete punch sequence for '.
                    "{$group['employee_code']} on ".
                    "{$group['attendance_date']}. ".
                    "Punch count: {$count}. Skipping."
                );

                continue;
            }

            $startAt = $timestamps->first();
            $endAt = $timestamps->last();

            if (
                ! $startAt ||
                ! $endAt ||
                $endAt->lessThanOrEqualTo($startAt)
            ) {
                continue;
            }

            $breaks = [];
            $breakSeconds = 0;

            for (
                $index = 1;
                $index < $count - 1;
                $index += 2
            ) {
                $breakStart = $timestamps->get($index);
                $breakEnd = $timestamps->get($index + 1);

                if (
                    ! $breakStart ||
                    ! $breakEnd ||
                    $breakEnd->lessThanOrEqualTo($breakStart)
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
            ? $attendanceDate->format('Y-m-d')
            : $attendanceDate;

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
            ! $parsedDate ||
            $parsedDate->format('d/m/Y_H:i') !== $date
        ) {
            throw new InvalidArgumentException(
                "Invalid date/time '{$date}'. ".
                'Expected format: d/m/Y_H:i.'
            );
        }

        return $parsedDate;
    }
}
