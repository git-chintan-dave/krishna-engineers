<?php

namespace App\Imports;

use App\Models\Section;
use App\Models\SectionSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SectionSizesImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private int $created = 0;

    private int $updated = 0;

    private array $errors = [];

    private array $validatedRows = [];

    public function collection(Collection $rows): void
    {
        $sectionNames = [];

        // Validate spreadsheet rows before writing anything.
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
                'section_name' => ['required', 'string', 'max:255'],
                'size' => ['required', 'string', 'max:255'],
                'weight' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->errors[] = "Row {$rowNumber}: {$message}";
                }

                continue;
            }

            $sectionKey = mb_strtolower($data['section_name']);
            $sectionNames[$sectionKey] = $data['section_name'];

            $this->validatedRows[] = [
                ...$data,
                'row_number' => $rowNumber,
            ];
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

            // Load sections once and match names case-insensitively.
            $sectionsByName = Section::query()
                ->get(['id', 'name'])
                ->keyBy(fn (Section $section) => mb_strtolower(trim($section->name)));

            $resolvedRows = [];
            $duplicateKeys = [];

            foreach ($this->validatedRows as $data) {
                $sectionKey = mb_strtolower(trim($data['section_name']));
                $section = $sectionsByName->get($sectionKey);

                if (! $section) {
                    $this->errors[] = "Row {$data['row_number']}: Section '{$data['section_name']}' does not exist.";

                    continue;
                }

                $sizeKey = mb_strtolower(trim($data['size']));
                $uniqueKey = $section->id.':'.$sizeKey;

                if (isset($duplicateKeys[$uniqueKey])) {
                    $this->errors[] = "Row {$data['row_number']}: Duplicate size '{$data['size']}' for section '{$data['section_name']}' in the file.";

                    continue;
                }

                $duplicateKeys[$uniqueKey] = true;

                $resolvedRows[] = [
                    'section_id' => $section->id,
                    'size' => $data['size'],
                    'weight' => $data['weight'],
                ];
            }

            if ($this->errors !== []) {
                throw ValidationException::withMessages([
                    'file' => $this->errors,
                ]);
            }

            $sectionIds = array_unique(
                array_column($resolvedRows, 'section_id'),
            );

            $existingSizes = SectionSize::query()
                ->whereIn('section_id', $sectionIds)
                ->get(['id', 'section_id', 'size'])
                ->keyBy(fn (SectionSize $item) => $item->section_id.':'.mb_strtolower(trim($item->size)));

            $now = now();
            $records = [];

            foreach ($resolvedRows as $data) {
                $key = $data['section_id'].':'.mb_strtolower(trim($data['size']));
                $existing = $existingSizes->get($key);

                $records[] = [
                    'section_id' => $data['section_id'],
                    'size' => $data['size'],
                    'weight' => $data['weight'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($existing) {
                    $this->updated++;
                } else {
                    $this->created++;
                }
            }

            if ($records === []) {
                return;
            }

            SectionSize::upsert(
                $records,
                ['section_id', 'size'],
                ['weight', 'updated_at'],
            );
        });
    }

    private function normalizeRow(array $row): array
    {
        return [
            'section_name' => $this->nullableString($row['section_name'] ?? null),
            'size' => $this->nullableString($row['size'] ?? null),
            'weight' => $this->nullableString($row['weight'] ?? null),
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
