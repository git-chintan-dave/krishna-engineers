<?php

namespace App\Imports;

use App\Enums\UserRole;
use App\Enums\UserShift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EmployeesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private int $created = 0;

    private int $updated = 0;

    private array $errors = [];

    private array $validatedRows = [];

    public function collection(Collection $rows): void
    {
        $employeeCodes = [];

        // Validate every row before saving any records.
        foreach ($rows as $index => $row) {
            $row = $row->toArray();
            $rowNumber = $index + 2;

            if (collect($row)->every(
                fn ($value) => $value === null || trim((string) $value) === ''
            )) {
                continue;
            }

            $data = $this->normalizeRow($row);

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'employee_code' => ['required', 'string', 'max:50'],
                'role' => ['required', Rule::enum(UserRole::class)],
                'phone_number' => ['nullable', 'string', 'max:30'],
                'alternate_phone_number' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'joining_date' => ['nullable', 'date'],
                'shift' => ['nullable', Rule::enum(UserShift::class)],
                'salary' => ['nullable', 'numeric', 'min:0'],
                'incentive' => ['nullable', 'numeric', 'min:0'],
                'deduction' => ['nullable', 'numeric', 'min:0'],
                'aadhar_number' => ['nullable', 'string', 'max:20'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->errors[] = "Row {$rowNumber}: {$message}";
                }

                continue;
            }

            $code = $data['employee_code'];

            if (isset($employeeCodes[$code])) {
                $this->errors[] = "Row {$rowNumber}: Duplicate employee code '{$code}' in the file.";

                continue;
            }

            $employeeCodes[$code] = true;
            $this->validatedRows[] = $data;
        }

        if ($this->errors !== []) {
            throw ValidationException::withMessages([
                'file' => $this->errors,
            ]);
        }

        DB::transaction(function (): void {
            $now = now();
            $defaultPassword = Hash::make('password');

            $employeeCodes = array_column(
                $this->validatedRows,
                'employee_code',
            );

            // Fetch existing employees in one query.
            $existingCodes = User::query()
                ->whereIn('employee_code', $employeeCodes)
                ->pluck('employee_code')
                ->flip()
                ->all();

            $records = [];

            foreach ($this->validatedRows as $data) {
                $isNew = ! isset($existingCodes[$data['employee_code']]);

                $records[] = [
                    'employee_code' => $data['employee_code'],
                    'name' => $data['name'],
                    'phone_number' => $data['phone_number'],
                    'alternate_phone_number' => $data['alternate_phone_number'],
                    'email' => $data['email'],
                    'joining_date' => $data['joining_date'],
                    'role' => $data['role'],
                    'shift' => $data['shift'],
                    'salary' => $data['salary'],
                    'incentive_per_day' => $data['incentive'],
                    'deduction_per_day' => $data['deduction'],
                    'aadhar_number' => $data['aadhar_number'],
                    'password' => $defaultPassword,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($isNew) {
                    $this->created++;
                } else {
                    $this->updated++;
                }
            }

            if ($records === []) {
                return;
            }

            User::upsert(
                $records,
                ['employee_code'],
                [
                    'name',
                    'phone_number',
                    'alternate_phone_number',
                    'email',
                    'joining_date',
                    'role',
                    'shift',
                    'salary',
                    'incentive_per_day',
                    'deduction_per_day',
                    'aadhar_number',
                    'updated_at',
                ],
            );
        });
    }

    private function normalizeRow(array $row): array
    {
        $role = $this->normalizeEnumValue($row['role'] ?? null);
        $shift = $this->normalizeEnumValue($row['shift'] ?? null);

        return [
            'name' => $this->nullableString($row['name'] ?? null),
            'employee_code' => $this->nullableString($row['employee_code'] ?? null),
            'phone_number' => $this->nullableString($row['phone_number'] ?? null),
            'alternate_phone_number' => $this->nullableString($row['alternate_phone_number'] ?? null),
            'email' => $this->nullableString($row['email'] ?? null),
            'joining_date' => $this->normalizeDate($row['joining_date'] ?? null),
            'role' => $role,
            'shift' => $shift,
            'salary' => $this->nullableString($row['salary'] ?? null) ?? '0',
            'incentive' => $this->nullableString($row['incentive'] ?? null) ?? '0',
            'deduction' => $this->nullableString($row['deduction'] ?? null) ?? '0',
            'aadhar_number' => $this->nullableString($row['aadhar_number'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function normalizeEnumValue(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        return strtolower(str_replace([' ', '-'], '_', $value));
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            // Leave invalid input for the date validator to reject.
            return (string) $value;
        }
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }
}
