<?php

namespace App\Imports;

use App\Exceptions\AttendanceImportException;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Throwable;

class AttendanceImport implements ToCollection, WithCalculatedFormulas
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function collection(
        Collection $rows,
    ): void {
        $employeeCode = $this->extractEmployeeCode(
            $rows,
        );

        if (blank($employeeCode)) {
            throw new AttendanceImportException(
                'The attendance report does not contain a valid employee code.',
            );
        }

        $user = $this->findEmployee(
            $employeeCode,
        );

        foreach ($rows as $row) {
            $this->importAttendanceRow(
                $user,
                $row,
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Employee
    |--------------------------------------------------------------------------
    */

    private function extractEmployeeCode(
        Collection $rows,
    ): ?string {
        /*
         * Employee code is stored in B3.
         */
        $employeeCode = $rows
            ->get(2)
            ?->get(1);

        return blank($employeeCode)
            ? null
            : trim((string) $employeeCode);
    }

    private function findEmployee(
        string $employeeCode,
    ): User {
        $user = User::query()
            ->where(
                'employee_code',
                $employeeCode,
            )
            ->first();

        if (! $user) {
            throw new AttendanceImportException(
                "Employee code {$employeeCode} was not found.",
            );
        }

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Attendance Row
    |--------------------------------------------------------------------------
    */

    private function importAttendanceRow(
        User $user,
        mixed $row,
    ): void {
        $row = $row instanceof Collection
            ? $row
            : collect($row);

        $attendanceDate = $this->parseDate(
            $row->get(0),
        );

        if ($attendanceDate === null) {
            return;
        }

        /*
         * Column C contains first IN.
         */
        $startTime = $this->parseTime(
            $row->get(2),
        );

        if ($startTime === null) {
            return;
        }

        /*
         * Column R contains final OUT.
         */
        $endTime = $this->parseTime(
            $row->get(17),
        );

        $startAt = $this->combineDateAndTime(
            $attendanceDate,
            $startTime,
        );

        $endAt = $endTime !== null
            ? $this->combineDateAndTime(
                $attendanceDate,
                $endTime,
            )
            : null;

        /*
         * Night shift:
         * If final OUT is earlier than first IN,
         * it belongs to the following day.
         */
        if (
            $endAt?->lt($startAt)
        ) {
            $endAt->addDay();
        }

        $breaks = $this->extractBreaks(
            $row,
            $attendanceDate,
        );

        $this->attendanceService->import(
            user: $user,
            attendanceDate: $attendanceDate,
            startAt: $startAt,
            endAt: $endAt,
            breaks: $breaks,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Breaks
    |--------------------------------------------------------------------------
    */

    private function parseDate(
        mixed $value,
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::createFromTimestamp(
                    ((float) $value - 25569) * 86400,
                )->toDateString();
            } catch (Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);

        foreach ([
            'd-m-Y',
            'd/m/Y',
            'Y-m-d',
            'm/d/Y',
            'd.m.Y',
        ] as $format) {
            try {
                return Carbon::createFromFormat(
                    $format,
                    $value,
                )->toDateString();
            } catch (Throwable) {
                // Try next format.
            }
        }

        try {
            return Carbon::parse(
                $value,
            )->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Excel Parsing
    |--------------------------------------------------------------------------
    */

    private function parseTime(
        mixed $value,
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('H:i:s');
        }

        if (is_numeric($value)) {
            $fraction = (float) $value;

            /*
             * Excel time values are represented as a
             * fraction of a 24-hour day.
             */
            if (
                $fraction >= 0
                && $fraction < 1
            ) {
                $seconds = (int) round(
                    $fraction * 86400,
                );

                $seconds %= 86400;

                return gmdate(
                    'H:i:s',
                    $seconds,
                );
            }
        }

        $value = trim((string) $value);

        /*
         * Values such as "--:--" represent no punch.
         */
        if (
            $value === '--:--'
            || $value === '--:--:--'
            || $value === '-'
        ) {
            return null;
        }

        foreach ([
            'H:i:s',
            'H:i',
            'g:i A',
            'g:i:s A',
        ] as $format) {
            try {
                return Carbon::createFromFormat(
                    $format,
                    $value,
                )->format('H:i:s');
            } catch (Throwable) {
                // Try next format.
            }
        }

        try {
            return Carbon::parse(
                $value,
            )->format('H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function combineDateAndTime(
        string $date,
        string $time,
    ): Carbon {
        return Carbon::parse(
            "{$date} {$time}",
        );
    }

    private function extractBreaks(
        Collection $row,
        string $attendanceDate,
    ): array {
        $breaks = [];

        /*
         * D:Q contain OUT/IN pairs:
         *
         * D/E = Out1/In2
         * F/G = Out2/In3
         * H/I = Out3/In4
         * J/K = Out4/In5
         * L/M = Out5/In6
         * N/O = Out6/In7
         * P/Q = Out7/In8
         */
        for (
            $column = 3;
            $column <= 16;
            $column += 2
        ) {
            $outTime = $this->parseTime(
                $row->get($column),
            );

            $inTime = $this->parseTime(
                $row->get($column + 1),
            );

            if (
                $outTime === null
                || $inTime === null
            ) {
                continue;
            }

            $breakStart = $this->combineDateAndTime(
                $attendanceDate,
                $outTime,
            );

            $breakEnd = $this->combineDateAndTime(
                $attendanceDate,
                $inTime,
            );

            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }

            $breaks[] = [
                'start_at' => $breakStart,
                'end_at' => $breakEnd,
                'duration_seconds' => max(
                    0,
                    (int) $breakStart->diffInSeconds(
                        $breakEnd,
                    ),
                ),
            ];
        }

        return $breaks;
    }
}
