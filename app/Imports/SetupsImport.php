<?php

namespace App\Imports;

use App\Enums\Status;
use App\Models\Setup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SetupsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private int $created = 0;

    private int $updated = 0;

    private array $errors = [];

    private array $validatedRows = [];

    public function collection(Collection $rows): void
    {
        $setupNames = [];

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
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->errors[] = "Row {$rowNumber}: {$message}";
                }

                continue;
            }

            $name = $data['name'];

            if (isset($setupNames[$name])) {
                $this->errors[] = "Row {$rowNumber}: Duplicate setup name '{$name}' in the file.";

                continue;
            }

            $setupNames[$name] = true;
            $this->validatedRows[] = $data;
        }

        if ($this->errors !== []) {
            throw ValidationException::withMessages([
                'file' => $this->errors,
            ]);
        }

        DB::transaction(function (): void {
            if ($this->validatedRows === []) {
                return;
            }

            $names = array_column($this->validatedRows, 'name');

            $existingNames = Setup::query()
                ->whereIn('name', $names)
                ->pluck('name')
                ->flip()
                ->all();

            $now = now();
            $records = [];

            foreach ($this->validatedRows as $data) {
                $name = $data['name'];
                $isNew = ! isset($existingNames[$name]);

                $records[] = [
                    'name' => $name,
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

            Setup::upsert(
                $records,
                ['name'],
                ['updated_at'],
            );
        });
    }

    private function normalizeRow(array $row): array
    {
        return [
            'name' => $this->nullableString($row['name'] ?? null),
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
