<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use BackedEnum;

class DashboardService
{
    /**
     * Get today's attendance card data for the authenticated employee.
     */
    public function todayShift(User $user): array
    {
        $shift = $user->shift;

        $shiftValue = $shift instanceof BackedEnum
            ? $shift->value
            : (string) ($shift ?? '');

        $attendance = Attendance::query()
            ->with('breaks')
            ->forUser((int) $user->id)
            ->whereDate('attendance_date', today()->toDateString())
            ->first();

        $targetSeconds = 12 * 60 * 60;

        if (! $attendance) {
            return [
                'status' => 'not_recorded',
                'statusLabel' => 'Not recorded',
                'shiftLabel' => $this->shiftLabel($shiftValue),
                'checkIn' => null,
                'checkOut' => null,
                'totalWorkingSeconds' => 0,
                'breakSeconds' => 0,
                'effectiveSeconds' => 0,
                'undertimeSeconds' => $targetSeconds,
                'targetSeconds' => $targetSeconds,
                'isComplete' => false,
                'breaks' => [],
            ];
        }

        return [
            'status' => 'present',
            'statusLabel' => $attendance->isOpen()
                ? 'On shift'
                : 'Present',
            'shiftLabel' => $this->shiftLabel($shiftValue),
            'checkIn' => $attendance->start_at?->format('h:i A'),
            'checkOut' => $attendance->end_at?->format('h:i A'),
            'totalWorkingSeconds' => (int) $attendance->total_working_seconds,
            'breakSeconds' => (int) $attendance->break_seconds,
            'effectiveSeconds' => (int) $attendance->effective_working_seconds,
            'undertimeSeconds' => (int) $attendance->undertime_seconds,
            'targetSeconds' => $targetSeconds,
            'isComplete' => ! $attendance->isOpen(),
            'breaks' => $attendance->breaks
                ->map(fn ($break) => [
                    'startAt' => $break->start_at?->format('h:i A'),
                    'endAt' => $break->end_at?->format('h:i A'),
                    'durationSeconds' => (int) $break->duration_seconds,
                    'isOngoing' => $break->end_at === null,
                ])
                ->values()
                ->all(),
        ];
    }

    private function shiftLabel(string $shift): string
    {
        return match ($shift) {
            'day' => 'Day shift',
            'night' => 'Night shift',
            '8_hr' => '8-hour shift',
            '8_hr_total' => '8-hour total shift',
            default => 'Shift',
        };
    }
}
