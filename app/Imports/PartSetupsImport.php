<?php

namespace App\Imports;

use App\Models\Part;
use App\Models\PartSetup;
use App\Models\Setup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PartSetupsImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    private int $created = 0;

    private int $updated = 0;

    private int $nextRowNumber = 2;

    private array $seenKeys = [];

    private ?Collection $partsByName = null;

    private ?Collection $setupsByName = null;

    public function collection(Collection $rows): void
    {
        $validatedRows = [];
        $errors = [];

        foreach ($rows as $row) {
            $rowNumber = $this->nextRowNumber++;
            $data = $this->normalizeRow($row->toArray());

            if (collect($data)->every(fn ($value) => $value === null)) {
                continue;
            }

            $validator = Validator::make($data, [
                'part' => ['required', 'string', 'max:255'],
                'setup' => ['required', 'string', 'max:255'],
                'machine_cycle_time' => [
                    'required',
                    'integer',
                    'min:0',
                    'max:4294967295',
                ],
                'actual_cycle_time' => [
                    'required',
                    'integer',
                    'min:0',
                    'max:4294967295',
                ],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Row {$rowNumber}: {$message}";
                }

                continue;
            }

            $validatedRows[] = [
                ...$data,
                'row_number' => $rowNumber,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        if ($validatedRows === []) {
            return;
        }

        $this->loadLookupData();

        $records = [];

        foreach ($validatedRows as $data) {
            $partName = mb_strtolower(trim($data['part']));
            $setupName = mb_strtolower(trim($data['setup']));

            $matchingParts = $this->partsByName->get($partName, collect());
            $setup = $this->setupsByName->get($setupName);

            if ($matchingParts->count() !== 1) {
                $reason = $matchingParts->isEmpty()
                    ? "Part '{$data['part']}' was not found."
                    : "Part '{$data['part']}' matches multiple parts. The name must identify exactly one part.";

                $errors[] = "Row {$data['row_number']}: {$reason}";

                continue;
            }

            if (! $setup) {
                $errors[] = "Row {$data['row_number']}: Setup '{$data['setup']}' was not found.";

                continue;
            }

            $part = $matchingParts->first();
            $key = "{$part->id}:{$setup->id}";

            if (isset($this->seenKeys[$key])) {
                $errors[] = "Row {$data['row_number']}: Duplicate Part + Setup combination in the uploaded file.";

                continue;
            }

            $this->seenKeys[$key] = true;

            $records[] = [
                'uuid' => (string) Str::uuid(),
                'part_id' => $part->id,
                'setup_id' => $setup->id,
                'machine_cycle_time_seconds' => (int) $data['machine_cycle_time'],
                'actual_cycle_time_seconds' => (int) $data['actual_cycle_time'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        if ($records === []) {
            return;
        }

        DB::transaction(function () use ($records): void {
            $partIds = array_unique(array_column($records, 'part_id'));
            $setupIds = array_unique(array_column($records, 'setup_id'));

            $existingKeys = PartSetup::query()
                ->whereIn('part_id', $partIds)
                ->whereIn('setup_id', $setupIds)
                ->get(['part_id', 'setup_id'])
                ->mapWithKeys(fn (PartSetup $partSetup) => [
                    "{$partSetup->part_id}:{$partSetup->setup_id}" => true,
                ]);

            foreach ($records as $record) {
                $key = "{$record['part_id']}:{$record['setup_id']}";

                if ($existingKeys->has($key)) {
                    $this->updated++;
                } else {
                    $this->created++;
                }
            }

            foreach (array_chunk($records, 500) as $batch) {
                PartSetup::upsert(
                    $batch,
                    ['part_id', 'setup_id'],
                    [
                        'machine_cycle_time_seconds',
                        'actual_cycle_time_seconds',
                        'updated_at',
                    ],
                );
            }
        });
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function loadLookupData(): void
    {
        if ($this->partsByName === null) {
            $this->partsByName = Part::query()
                ->get(['id', 'name'])
                ->groupBy(fn (Part $part) => mb_strtolower(trim($part->name)));
        }

        if ($this->setupsByName === null) {
            $this->setupsByName = Setup::query()
                ->get(['id', 'name'])
                ->keyBy(fn (Setup $setup) => mb_strtolower(trim($setup->name)));
        }
    }

    private function normalizeRow(array $row): array
    {
        return [
            'part' => $this->nullableString($row['part'] ?? null),
            'setup' => $this->nullableString($row['setup'] ?? null),
            'machine_cycle_time' => $this->nullableString(
                $row['machine_cycle_time'] ?? null
            ),
            'actual_cycle_time' => $this->nullableString(
                $row['actual_cycle_time'] ?? null
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
