<?php

namespace App\Imports;

use App\Enums\Status;
use App\Enums\ToolType;
use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ToolsImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    private int $created = 0;

    private int $updated = 0;

    private int $nextRowNumber = 2;

    private array $seenKeys = [];

    public function collection(Collection $rows): void
    {
        $records = [];
        $errors = [];

        foreach ($rows as $row) {
            $rowNumber = $this->nextRowNumber++;
            $data = $this->normalizeRow($row->toArray());

            if (collect($data)->every(fn ($value) => $value === null)) {
                continue;
            }

            $validator = Validator::make($data, [
                'type' => [
                    'required',
                    'string',
                    Rule::in(array_map(
                        fn (ToolType $type) => $type->value,
                        ToolType::cases(),
                    )),
                ],
                'name' => ['required', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Row {$rowNumber}: {$message}";
                }

                continue;
            }

            $type = $this->resolveType($data['type']);

            if ($type === null) {
                $errors[] = "Row {$rowNumber}: Invalid tool type '{$data['type']}'.";

                continue;
            }

            $key = mb_strtolower($type)
                . '|'
                . mb_strtolower(trim($data['name']));

            if (isset($this->seenKeys[$key])) {
                $errors[] = "Row {$rowNumber}: Duplicate Type + Name combination in the uploaded file.";

                continue;
            }

            $this->seenKeys[$key] = true;

            $records[] = [
                'type' => $type,
                'name' => trim($data['name']),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        if ($records === []) {
            return;
        }

        DB::transaction(function () use ($records): void {
            $now = now();

            $existingTools = Tool::query()
                ->whereIn('type', array_unique(array_column($records, 'type')))
                ->whereIn('name', array_unique(array_column($records, 'name')))
                ->get(['id', 'type', 'name'])
                ->keyBy(fn (Tool $tool) => mb_strtolower($tool->type)
                    . '|'
                    . mb_strtolower(trim($tool->name)));

            $insertRecords = [];

            foreach ($records as $record) {
                $key = mb_strtolower($record['type'])
                    . '|'
                    . mb_strtolower($record['name']);

                if ($existingTools->has($key)) {
                    $this->updated++;

                    continue;
                }

                $insertRecords[] = [
                    'uuid' => (string) Str::uuid(),
                    'type' => $record['type'],
                    'name' => $record['name'],
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $this->created++;
            }

            foreach (array_chunk($insertRecords, 500) as $batch) {
                Tool::query()->insert($batch);
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
            'type' => $this->nullableString($row['type'] ?? null),
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

    private function resolveType(string $value): ?string
    {
        foreach (ToolType::cases() as $type) {
            if (mb_strtolower($type->value) === mb_strtolower(trim($value))) {
                return $type->value;
            }
        }

        return null;
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
