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

        foreach (['base_url', 'corporate_id', 'username', 'password'] as $key) {
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

        /*
         * Confirm this credential format with eTimeOffice.
         * It follows the format currently configured for this integration.
         */
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
                    $parameters
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

        /*
         * TODO: Map the actual API response into normalized attendance
         * records after confirming the response structure.
         *
         * Each normalized record must contain:
         * - employee_code
         * - attendance_date (Y-m-d)
         * - start_at (CarbonInterface)
         * - end_at (CarbonInterface|null)
         *
         * Do not process raw API records until this mapping is implemented.
         */
        $punchRecords = $this->normalizePunchRecords($data);

        if ($punchRecords === null) {
            $this->warn(
                'Records were fetched but not saved. '.
                'The API response mapping must be implemented first.'
            );

            $this->line('Response type: '.(array_is_list($data) ? 'List' : 'Object'));
            $this->line('Top-level fields: '.implode(', ', array_keys($data)));

            return self::FAILURE;
        }

        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($punchRecords as $record) {
            $user = User::query()
                ->where('employee_code', $record['employee_code'])
                ->first();

            if (! $user) {
                $this->warn(
                    "Employee not found: {$record['employee_code']}"
                );

                $skipped++;

                continue;
            }

            $exists = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate(
                    'attendance_date',
                    $record['attendance_date']
                )
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            try {
                $attendanceService->import(
                    user: $user,
                    attendanceDate: $record['attendance_date'],
                    startAt: $record['start_at'],
                    endAt: $record['end_at'] ?? null,
                );

                $created++;
            } catch (AttendanceImportException $exception) {
                $this->warn(
                    "Skipped {$record['employee_code']} on ".
                    "{$record['attendance_date']}: ".
                    $exception->getMessage()
                );

                $skipped++;
            } catch (Throwable $exception) {
                report($exception);

                $this->error(
                    "Failed to import {$record['employee_code']} on ".
                    "{$record['attendance_date']}."
                );

                $failed++;
            }
        }

        $this->newLine();
        $this->info("Attendance created: {$created}");
        $this->info("Attendance skipped: {$skipped}");
        $this->info("Attendance failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
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

            $employeeCode = trim((string) ($punch['Empcode'] ?? ''));
            $dateString = trim((string) ($punch['DateString'] ?? ''));
            $inTime = trim((string) ($punch['INTime'] ?? ''));
            $outTime = trim((string) ($punch['OUTTime'] ?? ''));
            $status = strtoupper(trim((string) ($punch['Status'] ?? '')));

            // Skip records without a valid employee code or attendance date.
            if ($employeeCode === '' || $dateString === '') {
                continue;
            }

            // Skip absent, weekly-off, and other non-working statuses.
            if ($status !== 'P') {
                continue;
            }

            // "--:--" indicates that no punch time is available.
            if (
                $inTime === '--:--' ||
                ! preg_match('/^\d{2}:\d{2}$/', $inTime)
            ) {
                continue;
            }

            // Parse the attendance date.
            try {
                $attendanceDate = Carbon::createFromFormat(
                    '!d/m/Y',
                    $dateString
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

            // Combine the attendance date with the punch-in time.
            try {
                $startAt = Carbon::createFromFormat(
                    '!Y-m-d H:i',
                    $attendanceDate->format('Y-m-d').' '.$inTime
                );
            } catch (Throwable) {
                continue;
            }

            if (! $startAt) {
                continue;
            }

            // OUTTime may be missing when an employee has not punched out.
            $endAt = null;

            if (
                $outTime !== '--:--' &&
                preg_match('/^\d{2}:\d{2}$/', $outTime)
            ) {
                try {
                    $endAt = Carbon::createFromFormat(
                        '!Y-m-d H:i',
                        $attendanceDate->format('Y-m-d').' '.$outTime
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
                    // Handle an overnight shift.
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
            $parsedDate = Carbon::createFromFormat('!d/m/Y', $date);
        } catch (Throwable) {
            $parsedDate = false;
        }

        if (! $parsedDate || $parsedDate->format('d/m/Y') !== $date) {
            throw new InvalidArgumentException(
                "Invalid date '{$date}'. Expected format: d/m/Y."
            );
        }

        return $parsedDate;
    }
}
