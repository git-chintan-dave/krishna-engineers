<?php

namespace App\Imports\Attendance;

use Maatwebsite\Excel\Concerns\ToArray;

class AttendanceReportImport implements ToArray
{
    /**
     * Receive the complete worksheet as an array.
     *
     * The attendance report contains metadata rows before
     * the actual attendance table, so parsing is handled by
     * AttendanceService.
     */
    public function array(
        array $array,
    ): array {
        return $array;
    }
}
