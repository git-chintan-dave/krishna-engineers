<?php

namespace App\Services;

use App\Models\Part;
use App\Models\PartSetup;
use App\Models\Setup;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartSetupService
{
    /**
     * Create a part setup configuration.
     */
    public function create(Part $part, Setup $setup, array $data = []): PartSetup
    {
        $this->ensureActive($part, $setup);

        $exists = PartSetup::query()
            ->where('part_id', $part->id)
            ->where('setup_id', $setup->id)
            ->exists();

        if ($exists) {
            throw new RuntimeException(
                'This part and setup combination already exists.'
            );
        }

        return DB::transaction(function () use ($part, $setup, $data) {
            return PartSetup::create([
                'part_id' => $part->id,
                'setup_id' => $setup->id,
                'image' => $data['image'] ?? null,
                'machine_cycle_time_seconds' => $data['machine_cycle_time_seconds'] ?? 0,
                'actual_cycle_time_seconds' => $data['actual_cycle_time_seconds'] ?? 0,
            ]);
        });
    }

    /**
     * Update a part setup configuration.
     */
    public function update(PartSetup $partSetup, array $data): PartSetup
    {
        if (isset($data['part_id']) || isset($data['setup_id'])) {
            $part = Part::findOrFail(
                $data['part_id'] ?? $partSetup->part_id
            );

            $setup = Setup::findOrFail(
                $data['setup_id'] ?? $partSetup->setup_id
            );

            $this->ensureActive($part, $setup);

            $duplicate = PartSetup::query()
                ->where('id', '!=', $partSetup->id)
                ->where('part_id', $part->id)
                ->where('setup_id', $setup->id)
                ->exists();

            if ($duplicate) {
                throw new RuntimeException(
                    'This part and setup combination already exists.'
                );
            }
        }

        $partSetup->update($data);

        return $partSetup->fresh(['part', 'setup']);
    }

    /**
     * Find a configuration for a part and setup.
     */
    public function find(Part $part, Setup $setup): ?PartSetup
    {
        return PartSetup::query()
            ->where('part_id', $part->id)
            ->where('setup_id', $setup->id)
            ->first();
    }

    /**
     * Ensure all related master records are active.
     */
    protected function ensureActive(Part $part, Setup $setup): void
    {
        if (! $part->isActive()) {
            throw new RuntimeException(
                'The selected part is inactive.'
            );
        }

        if (! $setup->isActive()) {
            throw new RuntimeException(
                'The selected setup is inactive.'
            );
        }
    }
}
