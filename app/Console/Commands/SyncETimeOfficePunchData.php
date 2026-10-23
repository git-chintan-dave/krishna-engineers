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

    protected $description = 'Sync attendance and breaks from eTimeOffice individual punch records';

    public function handle(AttendanceService $attendanceService): int
    {
        @set_time_limit(0);

        try {
            // The API does not return today's punch data.
            // By default, sync the previous complete day.
            $defaultDate = now()->subDay();

            $fromDate = $this->parseDate(
                $this->option('from')
                    ?: $defaultDate->copy()->startOfDay()->format('d/m/Y_H:i')
            );

            $toDate = $this->parseDate(
                $this->option('to')
                    ?: $defaultDate->copy()->endOfDay()->format('d/m/Y_H:i')
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

        $this->info('Starting eTimeOffice punch sync...');
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
            $this->info('No complete attendance records found.');

            return self::SUCCESS;
        }

        $employeeCodes = collect($attendanceRecords)
            ->pluck('employee_code')
            ->unique()
            ->values();

        $usersByEmployeeCode = User::query()
            ->whereIn('employee_code', $employeeCodes)
            ->get()
            ->keyBy('employee_code');

        $userIds = $usersByEmployeeCode
            ->pluck('id')
            ->values();

        $existingAttendance = Attendance::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('attendance_date', [
                $fromDate->format('Y-m-d'),
                $toDate->format('Y-m-d'),
            ])
            ->get([
                'user_id',
                'attendance_date',
            ])
            ->mapWithKeys(
                fn (Attendance $attendance) => [
                    $this->attendanceKey(
                        $attendance->user_id,
                        $attendance->attendance_date,
                    ) => true,
                ]
            )
            ->all();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        $this->info(
            'Processing '.count($attendanceRecords).' attendance record(s)...'
        );

        foreach ($attendanceRecords as $record) {
            $employeeCode = $record['employee_code'];

            /** @var User|null $user */
            $user = $usersByEmployeeCode->get($employeeCode);

            if (! $user) {
                $this->warn("Employee not found: {$employeeCode}");

                $skipped++;

                continue;
            }

            $attendanceDate = $record['attendance_date'];

            $key = $this->attendanceKey(
                $user->id,
                $attendanceDate,
            );

            /*
             * Do not overwrite existing attendance or manually entered breaks.
             */
            if (isset($existingAttendance[$key])) {
                $skipped++;

                continue;
            }

            try {
                $attendanceService->import(
                    user: $user,
                    attendanceDate: $attendanceDate,
                    startAt: $record['start_at'],
                    endAt: $record['end_at'],
                    breaks: $record['breaks'],
                );

                $existingAttendance[$key] = true;

                $created++;

                $this->line(
                    "Imported {$employeeCode} on {$attendanceDate}; ".
                    'breaks: '.count($record['breaks']).'; '.
                    'total break: '.
                    gmdate('H:i:s', $record['break_seconds'])
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
                    "Failed to import {$employeeCode} on {$attendanceDate}."
                );

                $failed++;
            }
        }

        $this->newLine();
        $this->info("Attendance created: {$created}");
        $this->info("Attendance skipped: {$skipped}");
        $this->info("Attendance failed: {$failed}");

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Convert raw punches into one attendance record per employee/date.
     *
     * Assumption:
     * Punches alternate between attendance IN and OUT events.
     *
     * First punch: attendance IN
     * Last punch: attendance OUT
     * Intermediate pairs: break OUT and break IN
     *
     * Incomplete sequences are skipped rather than guessed.
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
                ->unique(fn (Carbon $timestamp) => $timestamp->timestamp)
                ->sortBy(fn (Carbon $timestamp) => $timestamp->timestamp)
                ->values();

            $count = $timestamps->count();

            /*
             * At least two punches are required.
             * An even count is required for a complete alternating sequence.
             */
            if ($count < 2 || $count % 2 !== 0) {
                $this->warn(
                    "Incomplete punch sequence for ".
                    "{$group['employee_code']} on ".
                    "{$group['attendance_date']}. ".
                    "Punch count: {$count}. Skipping."
                );

                continue;
            }

            $startAt = $timestamps->first();
            $endAt = $timestamps->last();

            $breaks = [];
            $breakSeconds = 0;

            /*
             * Exclude the first and last punches.
             * Pair the intermediate punches into break intervals.
             */
            for ($index = 1; $index < $count - 1; $index += 2) {
                $breakStart = $timestamps->get($index);
                $breakEnd = $timestamps->get($index + 1);

                if ($breakEnd->lessThanOrEqualTo($breakStart)) {
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

            if ($endAt->lessThanOrEqualTo($startAt)) {
                continue;
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
                "Invalid date/time '{$date}'. Expected format: d/m/Y_H:i."
            );
        }

        return $parsedDate;
    }
}
