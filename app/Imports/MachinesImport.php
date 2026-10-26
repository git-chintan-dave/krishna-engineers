<?php

namespace App\Imports;

use App\Enums\Status;
use App\Models\Machine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MachinesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private int $created = 0;

    private int $updated = 0;

    private array $errors = [];

    private array $validatedRows = [];

    public function collection(Collection $rows): void
    {
        $machineNumbers = [];

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
                'machine_number' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->errors[] = "Row {$rowNumber}: {$message}";
                }

                continue;
            }

            $number = $data['machine_number'];

            if ($number !== null) {
                if (isset($machineNumbers[$number])) {
                    $this->errors[] = "Row {$rowNumber}: Duplicate machine number '{$number}' in the file.";

                    continue;
                }

                $machineNumbers[$number] = true;
            }

            $this->validatedRows[] = $data;
        }

        if ($this->errors !== []) {
            throw ValidationException::withMessages([
                'file' => $this->errors,
            ]);
        }

        DB::transaction(function (): void {
            $numbers = array_values(array_unique(array_filter(
                array_column($this->validatedRows, 'machine_number'),
                fn ($number) => $number !== null,
            )));

            $existingNumbers = Machine::query()
                ->whereIn('machine_number', $numbers)
                ->pluck('machine_number')
                ->flip()
                ->all();

            $now = now();
            $records = [];

            foreach ($this->validatedRows as $data) {
                $number = $data['machine_number'];

                // Rows without a machine number are always new records.
                $isNew = $number === null || ! isset($existingNumbers[$number]);

                $records[] = [
                    'name' => $data['name'],
                    'machine_number' => $number,
                    'status' => Status::ACTIVE->value,
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

            // Insert new machines and update existing machines in one operation.
            Machine::upsert(
                $records,
                ['machine_number'],
                ['name', 'updated_at'],
            );
        });
    }

    private function normalizeRow(array $row): array
    {
        return [
            'name' => $this->nullableString($row['name'] ?? null),
            'machine_number' => $this->nullableString(
                $row['number'] ?? $row['machine_number'] ?? null,
            ),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
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
