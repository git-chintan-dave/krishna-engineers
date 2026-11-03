<?php

namespace App\Exports\Salary;

use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class SalaryAttendanceSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomStartCell, WithEvents, WithHeadings, WithTitle
{
    private const int START_ROW = 6;

    private const int DATA_START_ROW = 7;

    private const string COLOR_PRIMARY = '334155';

    private const string COLOR_FOREGROUND = '171717';

    private const string COLOR_MUTED = 'F5F5F5';

    private const string COLOR_MUTED_FOREGROUND = '737373';

    private const string COLOR_BORDER = 'E5E5E5';

    private const string COLOR_HEADER = 'EEEEEE';

    private const string COLOR_WEEK_OFF = 'F5F5F5';

    private const string COLOR_WEEK_OFF_TEXT = '737373';

    private const string COLOR_MISSING = 'FFFAF2';

    private const string COLOR_MISSING_TEXT = '92400E';

    private const string COLOR_SUCCESS = '166534';

    private const string COLOR_SUCCESS_BG = 'F0FDF4';

    private int $maxBreaks = 0;

    private array $headings = [];

    private array $durationColumns = [];

    public function __construct(
        private Salary $salary,
        private Collection $history,
    ) {
        $this->maxBreaks = (int) (
            $this->history
                ->map(fn ($attendance) => $attendance->breaks?->count() ?? 0)
                ->max() ?? 0
        );

        $this->buildHeadings();
    }

    public function title(): string
    {
        return 'Attendance';
    }

    public function startCell(): string
    {
        return 'A'.self::START_ROW;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function collection(): Collection
    {
        return $this->history->map(function ($attendance) {
            $date = Carbon::parse($attendance->attendance_date);

            if ($attendance->is_missing) {
                return array_merge(
                    [
                        $date->format('d M Y'),
                        $date->format('l'),
                    ],
                    array_fill(0, count($this->headings) - 2, null),
                );
            }

            $breaks = $attendance->breaks
                ->sortBy('start_at')
                ->values();

            $row = [
                $date->format('d M Y'),
                $date->format('l'),
                $this->formatTime($attendance->start_at),
            ];

            for ($index = 0; $index < $this->maxBreaks; $index++) {
                $break = $breaks->get($index);

                $row[] = $this->formatTime($break?->start_at);
                $row[] = $this->formatTime($break?->end_at);
            }

            $row[] = $this->formatTime($attendance->end_at);
            $row[] = $this->secondsToExcelTime($attendance->total_working_seconds);
            $row[] = $this->secondsToExcelTime($attendance->break_seconds);
            $row[] = $this->secondsToExcelTime($attendance->effective_working_seconds);
            $row[] = $this->secondsToExcelTime($attendance->undertime_seconds);

            return $row;
        });
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->durationColumns as $column) {
            $formats[$column] = '[h]:mm';
        }

        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings));
                $lastAttendanceRow = self::DATA_START_ROW + $this->history->count() - 1;

                $this->writeHeader($sheet, $lastColumn);
                $this->styleAttendanceTable($sheet, $lastColumn, $lastAttendanceRow);
                $this->mergeMissingAttendanceRows($sheet, $lastColumn);
                $this->writeAttendanceSummary($sheet, $lastColumn, $lastAttendanceRow);
                $this->writeSalarySummary($sheet, $lastColumn, $lastAttendanceRow);

                $this->applySheetSettings($sheet, $lastColumn);
            },
        ];
    }

    private function buildHeadings(): void
    {
        $this->headings = [
            'Date',
            'Day',
            'Clock In',
        ];

        for ($index = 1; $index <= $this->maxBreaks; $index++) {
            $this->headings[] = "Break {$index} Start";
            $this->headings[] = "Break {$index} End";
        }

        $this->headings = array_merge($this->headings, [
            'Clock Out',
            'Total Hours',
            'Break Time',
            'Effective Hours',
            'Late By',
        ]);

        foreach ([
                     'Total Hours',
                     'Break Time',
                     'Effective Hours',
                     'Late By',
                 ] as $heading) {
            $index = array_search($heading, $this->headings, true);

            if ($index !== false) {
                $this->durationColumns[] = Coordinate::stringFromColumnIndex($index + 1);
            }
        }
    }

    private function writeHeader($sheet, string $lastColumn): void
    {
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', 'KRISHNA ENGINEERS');

        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A2', 'MONTHLY ATTENDANCE & SALARY STATEMENT');

        $sheet->setCellValue('A3', 'Employee');
        $sheet->setCellValue('B3', $this->salary->user->name);
        $sheet->setCellValue('D3', 'Employee Code');
        $sheet->setCellValue('E3', $this->salary->user->employee_code);

        $sheet->setCellValue('A4', 'Salary Period');
        $sheet->setCellValue(
            'B4',
            Carbon::parse($this->salary->salary_period_start)->format('d M Y')
            .' - '
            .Carbon::parse($this->salary->salary_period_end)->format('d M Y'),
        );

        $sheet->setCellValue('D4', 'Salary Status');
        $sheet->setCellValue('E4', $this->salary->status->label());

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 18,
                'color' => ['rgb' => self::COLOR_PRIMARY],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_MUTED],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => self::COLOR_MUTED_FOREGROUND],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_MUTED],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:E4')->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => self::COLOR_FOREGROUND],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::COLOR_BORDER],
                ],
            ],
        ]);

        $sheet->getStyle('A3:A4')->getFont()->setBold(true);
        $sheet->getStyle('D3:D4')->getFont()->setBold(true);

        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(22);
    }

    private function styleAttendanceTable($sheet, string $lastColumn, int $lastRow): void
    {
        $sheet->getStyle('A'.self::START_ROW.":{$lastColumn}".self::START_ROW)
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => self::COLOR_PRIMARY],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_HEADER],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => self::COLOR_BORDER],
                    ],
                ],
            ]);

        $sheet->getRowDimension(self::START_ROW)->setRowHeight(32);

        if ($lastRow < self::DATA_START_ROW) {
            return;
        }

        $sheet->getStyle('A'.self::DATA_START_ROW.":{$lastColumn}{$lastRow}")
            ->applyFromArray([
                'font' => [
                    'size' => 10,
                    'color' => ['rgb' => self::COLOR_FOREGROUND],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => self::COLOR_BORDER],
                    ],
                ],
            ]);

        $sheet->getStyle('A'.self::DATA_START_ROW.":B{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setAutoFilter('A'.self::START_ROW.":{$lastColumn}{$lastRow}");

        for ($row = self::DATA_START_ROW; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(21);
        }
    }

    private function mergeMissingAttendanceRows($sheet, string $lastColumn): void
    {
        foreach ($this->history->values() as $index => $attendance) {
            if (! $attendance->is_missing) {
                continue;
            }

            $row = self::DATA_START_ROW + $index;

            $isWeekOff = $attendance->day_status === 'Week Off';

            $status = $isWeekOff ? 'WEEK OFF' : 'NO RECORD';

            $background = $isWeekOff
                ? self::COLOR_WEEK_OFF
                : self::COLOR_MISSING;

            $textColor = $isWeekOff
                ? self::COLOR_WEEK_OFF_TEXT
                : self::COLOR_MISSING_TEXT;

            $sheet->mergeCells("C{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue("C{$row}", $status);

            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $textColor],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $background],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getRowDimension($row)->setRowHeight(23);
        }
    }

    private function writeAttendanceSummary($sheet, string $lastColumn, int $lastAttendanceRow): void
    {
        $startRow = max($lastAttendanceRow + 2, self::DATA_START_ROW + 1);

        $weekOffDays = $this->history->filter(
            fn ($attendance) => $attendance->day_status === 'Week Off',
        )->count();

        $missingDays = $this->history->filter(
            fn ($attendance) => $attendance->is_missing
                && $attendance->day_status !== 'Week Off',
        )->count();

        $summary = [
            [
                'Total Calendar Days',
                $this->history->count(),
                'Attendance Days',
                $this->salary->attendance_days,
            ],
            [
                'Week Off Days',
                $weekOffDays,
                'Missing Days',
                $missingDays,
            ],
            [
                'Working Days',
                $this->salary->working_days,
                'Late Days',
                $this->salary->late_days,
            ],
            [
                'Total Late By',
                $this->secondsToExcelTime($this->salary->total_undertime_seconds),
                'Average Hours / Day',
                $this->averageEffectiveHours(),
            ],
        ];

        $this->writeSectionTitle(
            $sheet,
            $startRow,
            $lastColumn,
            'ATTENDANCE SUMMARY',
        );

        $this->writeSummaryRows($sheet, $startRow + 1, $summary, [
            'time' => [
                'B' => [4],
                'D' => [4],
            ],
        ]);
    }

    private function writeSalarySummary($sheet, string $lastColumn, int $lastAttendanceRow): void
    {
        $attendanceSummaryStart = max(
            $lastAttendanceRow + 2,
            self::DATA_START_ROW + 1,
        );

        $salaryStart = $attendanceSummaryStart + 7;

        $summary = [
            [
                'Daily Base Salary',
                $this->salary->base_salary,
                'Working Days',
                $this->salary->working_days,
            ],
            [
                'Base Salary Payable',
                (float) $this->salary->base_salary
                * (float) $this->salary->working_days,
                'Attendance Days',
                $this->salary->attendance_days,
            ],
            [
                'Incentive Rate / Day',
                $this->salary->incentive_rate,
                'Incentive Amount',
                $this->salary->incentive,
            ],
            [
                'Deduction Rate / Day',
                $this->salary->deduction_rate,
                'Deduction Amount',
                $this->salary->deduction,
            ],
            [
                'UPAD',
                $this->salary->upad,
                'Paid Loan',
                $this->salary->paid_loan,
            ],
            [
                'Adjustment',
                $this->salary->adjustment,
                'Salary Status',
                $this->salary->status->label(),
            ],
            [
                'Bonus',
                $this->salary->bonus,
                'Adjustment',
                $this->salary->adjustment,
            ],
            [
                'Adjustment Reason',
                $this->salary->adjustment_reason ?: '—',
                'Salary Period',
                Carbon::parse($this->salary->salary_period_start)->format('d M Y')
                .' - '
                .Carbon::parse($this->salary->salary_period_end)->format('d M Y'),
            ],
            [
                'Paid At',
                $this->formatDateTime($this->salary->paid_at),
                'Total Late By',
                $this->secondsToExcelTime(
                    $this->salary->total_undertime_seconds,
                ),
            ],
            [
                'Final Payable',
                $this->salary->final_payable,
                '',
                '',
            ],
        ];

        $this->writeSectionTitle(
            $sheet,
            $salaryStart,
            $lastColumn,
            'SALARY SUMMARY',
        );

        $this->writeSummaryRows($sheet, $salaryStart + 1, $summary, [
            'currency' => [
                'B' => [1, 2, 3, 4, 5, 6, 9],
                'D' => [3, 4, 5],
            ],
            'time' => [
                'D' => [8],
            ],
        ]);

        $finalRow = $salaryStart + count($summary);

        $sheet->getStyle("A{$finalRow}:D{$finalRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => self::COLOR_SUCCESS],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_SUCCESS_BG],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => self::COLOR_SUCCESS],
                ],
            ],
        ]);

        $sheet->getStyle("B{$finalRow}")
            ->getNumberFormat()
            ->setFormatCode('"₹"#,##0.00');
    }

    private function writeSectionTitle(
        $sheet,
        int $row,
        string $lastColumn,
        string $title,
    ): void {
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->setCellValue("A{$row}", $title);

        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => self::COLOR_PRIMARY],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_MUTED],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::COLOR_BORDER],
                ],
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(25);
    }

    private function writeSummaryRows(
        $sheet,
        int $startRow,
        array $rows,
        array $formats = [],
    ): void {
        foreach ($rows as $index => $values) {
            $row = $startRow + $index;

            $sheet->setCellValue("A{$row}", $values[0]);
            $sheet->setCellValue("B{$row}", $values[1]);
            $sheet->setCellValue("C{$row}", $values[2]);
            $sheet->setCellValue("D{$row}", $values[3]);

            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'font' => [
                    'size' => 10,
                    'color' => ['rgb' => self::COLOR_FOREGROUND],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => self::COLOR_BORDER],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getStyle("A{$row}:C{$row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle("B{$row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("D{$row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("C{$row}")->getFont()->setBold(true);

            foreach (['A', 'C'] as $column) {
                $sheet->getStyle("{$column}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::COLOR_MUTED],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => self::COLOR_MUTED_FOREGROUND],
                    ],
                ]);
            }

            $sheet->getRowDimension($row)->setRowHeight(23);

            foreach (['B', 'D'] as $column) {
                if (in_array(
                    $index + 1,
                    $formats['currency'][$column] ?? [],
                    true,
                )) {
                    $sheet->getStyle("{$column}{$row}")
                        ->getNumberFormat()
                        ->setFormatCode('"₹"#,##0.00');
                }

                if (in_array(
                    $index + 1,
                    $formats['time'][$column] ?? [],
                    true,
                )) {
                    $sheet->getStyle("{$column}{$row}")
                        ->getNumberFormat()
                        ->setFormatCode('[h]:mm');
                }
            }
        }
    }

    private function applySheetSettings($sheet, string $lastColumn): void
    {
        $sheet->freezePane('C7');

        $sheet->getDefaultColumnDimension()->setWidth(15);

        $sheet->getColumnDimension('A')->setWidth(19);
        $sheet->getColumnDimension('B')->setWidth(17);

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4);

        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setScale(0);

        $sheet->getPageMargins()->setTop(0.4);
        $sheet->getPageMargins()->setBottom(0.4);
        $sheet->getPageMargins()->setLeft(0.25);
        $sheet->getPageMargins()->setRight(0.25);

        $sheet->setShowGridlines(false);

        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getFont()
            ->setName('Arial');
    }

    private function formatTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('H:i');
    }

    private function formatDate(mixed $value): ?string
    {
        return $value
            ? Carbon::parse($value)->format('d M Y')
            : null;
    }

    private function formatDateTime(mixed $value): ?string
    {
        return $value
            ? Carbon::parse($value)->format('d M Y H:i')
            : '—';
    }

    private function secondsToExcelTime(?int $seconds): ?float
    {
        if ($seconds === null) {
            return null;
        }

        return $seconds / 86400;
    }

    private function averageEffectiveHours(): ?float
    {
        $attendances = $this->history->filter(
            fn ($attendance) => ! $attendance->is_missing,
        );

        if ($attendances->isEmpty()) {
            return null;
        }

        $totalSeconds = $attendances->sum('effective_working_seconds');

        return ($totalSeconds / $attendances->count()) / 86400;
    }
}
