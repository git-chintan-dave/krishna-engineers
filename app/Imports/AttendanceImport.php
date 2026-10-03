<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class AttendanceImport implements ToCollection, WithCalculatedFormulas
{
    // Import
    public function collection(Collection $rows): void
    {
        $employeeCode = $this->extractEmployeeCode($rows);

        if (blank($employeeCode)) {
            throw ValidationException::withMessages([
                'file' => 'The attendance report does not contain a valid employee code.',
            ]);
        }

        $user = $this->findEmployee($employeeCode);

        foreach ($rows as $row) {
            $this->importAttendanceRow($user, $row);
        }
    }

    // Helpers
    private function extractEmployeeCode(Collection $rows): ?string
    {
        // Employee code is stored in B3.
        $employeeCode = $rows->get(2)?->get(1);

        return blank($employeeCode)
            ? null
            : trim((string) $employeeCode);
    }

    private function findEmployee(string $employeeCode): User
    {
        $user = User::query()
            ->where('employee_code', $employeeCode)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'file' => "Employee code {$employeeCode} was not found.",
            ]);
        }

        return $user;
    }

    private function importAttendanceRow(
        User $user,
        mixed $row,
    ): void {
        $row = $row instanceof Collection
            ? $row
            : collect($row);

        $attendanceDate = $this->parseDate($row->get(0));

        if ($attendanceDate === null) {
            return;
        }

        // Column C contains the first IN time.
        $startTime = $this->parseTime($row->get(2));

        // No IN punch means there is no attendance record to import.
        if ($startTime === null) {
            return;
        }

        // Column R contains the final OUT time.
        $endTime = $this->parseTime($row->get(17));

        $startAt = $this->combineDateAndTime(
            $attendanceDate,
            $startTime,
        );

        $endAt = $endTime !== null
            ? $this->combineDateAndTime($attendanceDate, $endTime)
            : null;

        // Night-shift attendance can end on the following day.
        if ($endAt?->lt($startAt)) {
            $endAt->addDay();
        }

        $breaks = $this->extractBreaks(
            $row,
            $attendanceDate,
        );

        $this->saveAttendance(
            $user,
            $attendanceDate,
            $startAt,
            $endAt,
            $breaks,
        );
    }

    private function extractBreaks(
        Collection $row,
        string $attendanceDate,
    ): array {
        $breaks = [];

        // D:Q contains OUT/IN break pairs.
        for ($column = 3; $column <= 16; $column += 2) {
            $outTime = $this->parseTime($row->get($column));
            $inTime = $this->parseTime($row->get($column + 1));

            if ($outTime === null || $inTime === null) {
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
                    (int) $breakStart->diffInSeconds($breakEnd),
                ),
            ];
        }

        return $breaks;
    }

    private function saveAttendance(
        User $user,
        string $attendanceDate,
        Carbon $startAt,
        ?Carbon $endAt,
        array $breaks,
    ): void {
        DB::transaction(function () use (
            $user,
            $attendanceDate,
            $startAt,
            $endAt,
            $breaks,
        ): void {
            $attendance = Attendance::query()
                ->firstOrNew([
                    'user_id' => $user->id,
                    'attendance_date' => $attendanceDate,
                ]);

            $attendance->fill([
                'start_at' => $startAt,
                'end_at' => $endAt,
            ]);

            $attendance->save();

            // Replace imported breaks so re-importing stays consistent.
            $attendance->breaks()->delete();

            foreach ($breaks as $break) {
                $attendance->breaks()->create($break);
            }

            $this->recalculateTotals($attendance);
        });
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || blank(trim((string) $value))) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(
                \DateTime::createFromInterface($value),
            )->toDateString();
        }

        $value = trim((string) $value);

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat(
                    $format,
                    $value,
                )->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTime(mixed $value): ?string
    {
        if ($value === null || blank(trim((string) $value))) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(
                \DateTime::createFromInterface($value),
            )->format('H:i:s');
        }

        $value = trim((string) $value);

        if (in_array($value, ['--:--', '00:00', '00:00:00'], true)) {
            return null;
        }

        // Excel stores time-only values as a fraction of a day.
        if (
            is_numeric($value)
            && (float) $value >= 0
            && (float) $value < 1
        ) {
            return Carbon::createFromTimestampUTC(
                (int) round((float) $value * 86400),
            )->format('H:i:s');
        }

        foreach (['H:i:s', 'H:i', 'G:i:s', 'G:i'] as $format) {
            try {
                return Carbon::createFromFormat(
                    $format,
                    $value,
                )->format('H:i:s');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function combineDateAndTime(
        string $date,
        string $time,
    ): Carbon {
        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            "{$date} {$time}",
        );
    }

    private function recalculateTotals(
        Attendance $attendance,
    ): Attendance {
        $totalWorkingSeconds = $this->calculateTotalWorkingSeconds(
            $attendance->start_at,
            $attendance->end_at,
        );

        $breakSeconds = (int) $attendance
            ->breaks()
            ->sum('duration_seconds');

        $attendance->update([
            'total_working_seconds' => $totalWorkingSeconds,
            'break_seconds' => $breakSeconds,
            'effective_working_seconds' => max(
                0,
                $totalWorkingSeconds - $breakSeconds,
            ),
        ]);

        return $attendance->fresh();
    }

    private function calculateTotalWorkingSeconds(
        mixed $startAt,
        mixed $endAt,
    ): int {
        if ($startAt === null || $endAt === null) {
            return 0;
        }

        $startAt = $startAt instanceof CarbonInterface
            ? $startAt
            : Carbon::parse($startAt);

        $endAt = $endAt instanceof CarbonInterface
            ? $endAt
            : Carbon::parse($endAt);

        return max(
            0,
            (int) $startAt->diffInSeconds($endAt),
        );
    }
}
