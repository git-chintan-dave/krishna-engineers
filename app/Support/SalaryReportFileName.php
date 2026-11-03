<?php

namespace App\Support;

use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SalaryReportFileName
{
    public static function make(Salary $salary): string
    {
        $employeeCode = Str::slug(
            $salary->user->employee_code ?: 'employee',
        );

        $employeeName = Str::slug($salary->user->name);

        $month = Carbon::parse(
            $salary->salary_period_start,
        )->format('M-Y');

        return sprintf(
            'salary-attendance-report-EMP-%s-%s-%s.xlsx',
            $employeeCode,
            $employeeName,
            $month,
        );
    }
}
