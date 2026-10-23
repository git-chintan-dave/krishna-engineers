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

class SyncETimeOfficePunches extends Command
{
    protected $signature = 'etimeoffice:sync
        {--empcode= : Optional employee code for testing}
        {--from= : Start date in d/m/Y format}
        {--to= : End date in d/m/Y format}';

    protected $description = 'Sync attendance punch records from eTimeOffice';

    public function handle(AttendanceService $attendanceService): int
    {
        /*
         * This command can also be invoked through a web request using
         * Artisan::call(). Do not allow PHP's web execution limit to terminate
         * a long attendance synchronization.
         */
        @set_time_limit(0);

        try {
            $fromDate = $this->parseDate(
                $this->option('from') ?: now()->format('d/m/Y')
            );

            $toDate = $this->parseDate(
                $this->option('to') ?: now()->format('d/m/Y')
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($fromDate->gt($toDate)) {
            $this->error(
                'The start date must be before or equal to the end date.'
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
            'Empcode' => $employeeCode,
            'FromDate' => $fromDate->format('d/m/Y'),
            'ToDate' => $toDate->format('d/m/Y'),
        ];

        $this->info('Starting eTimeOffice attendance sync...');
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
                    '/DownloadInOutPunchData',
                    $parameters,
                );

            $response->throw();

            $data = $response->json();

            if (! is_array($data)) {
                $this->error(
                    'The eTimeOffice API returned an unexpected response format.'
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

        $this->info('API request completed successfully.');

        $punchRecords = $this->normalizePunchRecords($data);

        if ($punchRecords === null) {
            $this->warn(
                'Records were fetched but not saved. '.
                'The API response mapping must be implemented first.'
            );

            $this->line(
                'Response type: '.
                (array_is_list($data) ? 'List' : 'Object')
            );

            $this->line(
                'Top-level fields: '.
                implode(', ', array_keys($data))
            );

            return self::FAILURE;
        }

        if ($punchRecords === []) {
            $this->newLine();
            $this->info('No attendance punch records found.');

            return self::SUCCESS;
        }

        /*
         * ------------------------------------------------------------------
         * Load users once
         * ------------------------------------------------------------------
         *
         * Previously:
         *
         * foreach ($punchRecords as $record) {
         *     User::where(...)->first();
         * }
         *
         * That creates one query per attendance record.
         */
        $employeeCodes = collect($punchRecords)
            ->pluck('employee_code')
            ->filter()
            ->unique()
            ->values();

        $usersByEmployeeCode = User::query()
            ->whereIn('employee_code', $employeeCodes)
            ->get()
            ->keyBy('employee_code');

        /*
         * ------------------------------------------------------------------
         * Load existing attendance once
         * ------------------------------------------------------------------
         *
         * We only need attendance for the requested date range and employees
         * returned by the API.
         */
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
            );

        $created = 0;
        $skipped = 0;
        $failed = 0;

        /*
         * Track newly-created records too.
         *
         * This protects us if the API itself returns duplicate records
         * for the same employee/date.
         */
        $processedAttendance = $existingAttendance->all();

        $this->info(
            'Processing '.count($punchRecords).' punch record(s)...'
        );

        foreach ($punchRecords as $record) {
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

            $attendanceKey = $this->attendanceKey(
                $user->id,
                $attendanceDate,
            );

            if (isset($processedAttendance[$attendanceKey])) {
                $skipped++;

                continue;
            }

            try {
                $attendanceService->import(
                    user: $user,
                    attendanceDate: $attendanceDate,
                    startAt: $record['start_at'],
                    endAt: $record['end_at'] ?? null,
                );

                /*
                 * Mark it as processed so duplicate records returned by the
                 * API cannot create duplicate attendance.
                 */
                $processedAttendance[$attendanceKey] = true;

                $created++;
            } catch (AttendanceImportException $exception) {
                $this->warn(
                    "Skipped {$employeeCode} on ".
                    "{$attendanceDate}: ".
                    $exception->getMessage()
                );

                $skipped++;
            } catch (Throwable $exception) {
                report($exception);

                $this->error(
                    "Failed to import {$employeeCode} on ".
                    "{$attendanceDate}."
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
     * Create a unique key for an employee attendance date.
     */
    private function attendanceKey(
        int $userId,
        string|Carbon $attendanceDate,
    ): string {
        $date = $attendanceDate instanceof Carbon
            ? $attendanceDate->format('Y-m-d')
            : $attendanceDate;

        return "{$userId}:{$date}";
    }

    /**
     * Normalize eTimeOffice punch records into attendance records.
     *
     * Each returned record contains:
     * - employee_code
     * - attendance_date (Y-m-d)
     * - start_at (Carbon)
     * - end_at (Carbon|null)
     */
    private function normalizePunchRecords(array $data): ?array
    {
        if (
            ($data['Error'] ?? true) !== false ||
            ! isset($data['InOutPunchData']) ||
            ! is_array($data['InOutPunchData'])
        ) {
            return null;
        }

        $records = [];

        foreach ($data['InOutPunchData'] as $punch) {
            if (! is_array($punch)) {
                continue;
            }

            $employeeCode = trim(
                (string) ($punch['Empcode'] ?? '')
            );

            $dateString = trim(
                (string) ($punch['DateString'] ?? '')
            );

            $inTime = trim(
                (string) ($punch['INTime'] ?? '')
            );

            $outTime = trim(
                (string) ($punch['OUTTime'] ?? '')
            );

            $status = strtoupper(
                trim((string) ($punch['Status'] ?? ''))
            );

            /*
             * Skip records without employee code or attendance date.
             */
            if ($employeeCode === '' || $dateString === '') {
                continue;
            }

            /*
             * Keep current behaviour:
             * status filtering is intentionally disabled.
             *
             * If required later:
             *
             * if ($status !== 'P') {
             *     continue;
             * }
             */

            /*
             * "--:--" indicates that no punch-in time is available.
             */
            if (
                $inTime === '--:--' ||
                ! preg_match('/^\d{2}:\d{2}$/', $inTime)
            ) {
                continue;
            }

            /*
             * Parse attendance date.
             */
            try {
                $attendanceDate = Carbon::createFromFormat(
                    '!d/m/Y',
                    $dateString,
                );
            } catch (Throwable) {
                continue;
            }

            if (
                ! $attendanceDate ||
                $attendanceDate->format('d/m/Y') !== $dateString
            ) {
                continue;
            }

            /*
             * Combine attendance date with punch-in time.
             */
            try {
                $startAt = Carbon::createFromFormat(
                    '!Y-m-d H:i',
                    $attendanceDate->format('Y-m-d').' '.$inTime,
                );
            } catch (Throwable) {
                continue;
            }

            if (! $startAt) {
                continue;
            }

            /*
             * OUTTime may be missing when an employee has not punched out.
             */
            $endAt = null;

            if (
                $outTime !== '--:--' &&
                preg_match('/^\d{2}:\d{2}$/', $outTime)
            ) {
                try {
                    $endAt = Carbon::createFromFormat(
                        '!Y-m-d H:i',
                        $attendanceDate->format('Y-m-d').' '.$outTime,
                    );
                } catch (Throwable) {
                    $endAt = null;
                }

                if (
                    ! $endAt ||
                    $endAt->format('H:i') !== $outTime
                ) {
                    $endAt = null;
                } elseif ($endAt->lt($startAt)) {
                    /*
                     * Handle overnight shifts.
                     */
                    $endAt->addDay();
                }
            }

            $records[] = [
                'employee_code' => $employeeCode,
                'attendance_date' => $attendanceDate->format('Y-m-d'),
                'start_at' => $startAt,
                'end_at' => $endAt,
            ];
        }

        return $records;
    }

    private function parseDate(string $date): Carbon
    {
        try {
            $parsedDate = Carbon::createFromFormat(
                '!d/m/Y',
                $date,
            );
        } catch (Throwable) {
            $parsedDate = false;
        }

        if (
            ! $parsedDate ||
            $parsedDate->format('d/m/Y') !== $date
        ) {
            throw new InvalidArgumentException(
                "Invalid date '{$date}'. Expected format: d/m/Y."
            );
        }

        return $parsedDate;
    }
}
