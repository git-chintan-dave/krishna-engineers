<?php

namespace App\Imports;

use App\Enums\Status;
use App\Models\Part;
use App\Models\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PartsImport implements SkipsEmptyRows, ToCollection, WithChunkReading, WithHeadingRow
{
    private int $created = 0;

    private int $updated = 0;

    private array $errors = [];

    public function collection(Collection $rows): void
    {
        $validatedRows = [];
        $seenKeys = [];

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
                'new_part_name' => ['nullable', 'string', 'max:255'],
                'section_name' => ['nullable', 'string', 'max:255'],
                'cutting_size' => ['nullable', 'string', 'max:255'],
                'part_weight' => ['nullable', 'string', 'max:255'],
                'raw_material_weight' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->errors[] = "Row {$rowNumber}: {$message}";
                }

                continue;
            }

            $key = mb_strtolower(trim($data['name']))
                .'|'
                .mb_strtolower(trim($data['section_name'] ?? ''));

            if (isset($seenKeys[$key])) {
                $this->errors[] = "Row {$rowNumber}: Duplicate part name and section combination in the file.";

                continue;
            }

            $seenKeys[$key] = true;

            $validatedRows[] = [
                ...$data,
                'row_number' => $rowNumber,
            ];
        }

        if ($this->errors !== []) {
            throw ValidationException::withMessages([
                'file' => $this->errors,
            ]);
        }

        if ($validatedRows === []) {
            return;
        }

        DB::transaction(function () use ($validatedRows): void {
            // Load sections once per chunk and match names case-insensitively.
            $sectionsByName = Section::query()
                ->get(['id', 'name'])
                ->keyBy(fn (Section $section) => mb_strtolower(trim($section->name)));

            $resolvedRows = [];

            foreach ($validatedRows as $data) {
                $sectionName = $data['section_name'];
                $sectionId = null;

                if ($sectionName !== null) {
                    $section = $sectionsByName->get(
                        mb_strtolower(trim($sectionName))
                    );

                    if (! $section) {
                        $this->errors[] = "Row {$data['row_number']}: Section '{$sectionName}' does not exist.";

                        continue;
                    }

                    $sectionId = $section->id;
                }

                $resolvedRows[] = [
                    ...$data,
                    'section_id' => $sectionId,
                ];
            }

            if ($this->errors !== []) {
                throw ValidationException::withMessages([
                    'file' => $this->errors,
                ]);
            }

            // Find existing parts for this chunk.
            $names = array_values(array_unique(
                array_column($resolvedRows, 'name')
            ));

            $sectionIds = array_values(array_unique(array_filter(
                array_column($resolvedRows, 'section_id'),
                fn ($id) => $id !== null,
            )));

            $existingParts = Part::query()
                ->whereIn('name', $names)
                ->where(function ($query) use ($sectionIds, $resolvedRows) {
                    if ($sectionIds !== []) {
                        $query->whereIn('section_id', $sectionIds);
                    }

                    if (collect($resolvedRows)->contains(
                        fn ($row) => $row['section_id'] === null
                    )) {
                        $query->orWhereNull('section_id');
                    }
                })
                ->get(['id', 'name', 'section_id'])
                ->keyBy(fn (Part $part) => mb_strtolower(trim($part->name))
                    .'|'
                    .($part->section_id === null
                        ? ''
                        : (string) $part->section_id));

            $now = now();
            $records = [];

            foreach ($resolvedRows as $data) {
                $key = mb_strtolower(trim($data['name']))
                    .'|'
                    .($data['section_id'] === null
                        ? ''
                        : (string) $data['section_id']);

                $existing = $existingParts->get($key);

                $record = [
                    'name' => $data['name'],
                    'new_part_name' => $data['new_part_name'],
                    'section_id' => $data['section_id'],
                    'cutting_size' => $data['cutting_size'],
                    'part_weight' => $data['part_weight'],
                    'raw_material_weight' => $data['raw_material_weight'],
                    'updated_at' => $now,
                ];

                if ($existing) {
                    $record['id'] = $existing->id;
                    $this->updated++;
                } else {
                    $record['uuid'] = (string) Str::uuid();
                    $record['status'] = Status::ACTIVE->value;
                    $record['created_at'] = $now;
                    $this->created++;
                }

                $records[] = $record;
            }

            if ($records === []) {
                return;
            }

            // Separate inserts and updates to preserve existing status and image.
            $newRecords = array_filter(
                $records,
                fn ($record) => ! isset($record['id']),
            );

            $updateRecords = array_filter(
                $records,
                fn ($record) => isset($record['id']),
            );

            if ($newRecords !== []) {
                Part::query()->insert(array_values($newRecords));
            }

            foreach (array_chunk(array_values($updateRecords), 500) as $batch) {
                $ids = array_column($batch, 'id');

                $updates = array_map(function ($record) {
                    unset($record['id'], $record['updated_at']);

                    return $record;
                }, $batch);

                foreach ($updates as $index => $update) {
                    Part::query()
                        ->whereKey($ids[$index])
                        ->update($update);
                }
            }
        });
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function normalizeRow(array $row): array
    {
        return [
            'name' => $this->nullableString($row['name'] ?? null),
            'new_part_name' => $this->nullableString($row['new_part_name'] ?? null),
            'section_name' => $this->nullableString($row['section'] ?? null),
            'cutting_size' => $this->nullableString($row['cutting_size'] ?? null),
            'part_weight' => $this->nullableString($row['part_weight'] ?? null),
            'raw_material_weight' => $this->nullableString(
                $row['raw_material_weight'] ?? null
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
