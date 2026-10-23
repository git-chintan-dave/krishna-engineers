<?php

namespace App\Services\Lookup;

use App\Enums\RawMaterialTransactionType;
use App\Enums\SalaryStatus;
use App\Enums\Status;
use App\Enums\ToolTransactionType;
use App\Enums\ToolType;
use App\Enums\UserRole;
use App\Enums\UserShift;
use App\Models\Machine;
use App\Models\Part;
use App\Models\Section;
use App\Models\SectionSize;
use App\Models\Setup;
use App\Models\Tool;
use App\Models\User;
use InvalidArgumentException;

class LookupService
{
    /*
    |--------------------------------------------------------------------------
    | Enum Lookups
    |--------------------------------------------------------------------------
    */

    private const array ENUMS = [
        'status' => Status::class,
        'salary_status' => SalaryStatus::class,
        'raw_material_transaction_type' => RawMaterialTransactionType::class,
        'tool_transaction_type' => ToolTransactionType::class,
        'tool_type' => ToolType::class,
        'user_role' => UserRole::class,
        'user_shift' => UserShift::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Enum Options
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, array<int, array{value: string|int, label: string}>>
     */
    public function allEnums(): array
    {
        return $this->enums(array_keys(self::ENUMS));
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, array<int, array{value: string|int, label: string}>>
     */
    public function enums(array $keys): array
    {
        return collect($keys)
            ->mapWithKeys(fn (string $key): array => [
                $key => $this->enum($key),
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string|int, label: string}>
     */
    public function enum(string $key): array
    {
        $enumClass = self::ENUMS[$key] ?? null;

        if ($enumClass === null) {
            throw new InvalidArgumentException(
                "Unknown enum lookup [{$key}]."
            );
        }

        return $enumClass::options();
    }

    /*
    |--------------------------------------------------------------------------
    | Database Lookups
    |--------------------------------------------------------------------------
    */

    /**
     * Get active parts.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function parts(): array
    {
        return Part::query()
            ->where(
                'status',
                Status::ACTIVE->value,
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'cutting_size',
                'section_id',
            ])
            ->map(fn (Part $part): array => [
                'value' => $part->id,
                'label' => $part->name,
                'cutting_size' => $part->cutting_size,
                'section_id' => $part->section_id,
            ])
            ->all();
    }

    /**
     * Get all setups.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function setups(?int $partId = null): array
    {
        return Setup::query()
            ->when(
                $partId,
                fn ($query) => $query->whereHas(
                    'partSetups',
                    fn ($partSetupQuery) => $partSetupQuery->where(
                        'part_id',
                        $partId,
                    ),
                ),
            )
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Setup $setup): array => [
                'value' => $setup->id,
                'label' => $setup->name,
            ])
            ->all();
    }

    /**
     * Get active sections.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function sections(): array
    {
        return Section::query()
            ->where('status', Status::ACTIVE->value)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Section $section): array => [
                'value' => $section->id,
                'label' => $section->name,
            ])
            ->all();
    }

    /**
     * Get section sizes for a section.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function sectionSizes(int $sectionId): array
    {
        return SectionSize::query()
            ->where('section_id', $sectionId)
            ->orderBy('size')
            ->get(['id', 'size'])
            ->map(fn (SectionSize $sectionSize): array => [
                'value' => $sectionSize->id,
                'label' => $sectionSize->size,
            ])
            ->all();
    }

    /**
     * Get active machines.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function machines(): array
    {
        return Machine::query()
            ->where('status', Status::ACTIVE->value)
            ->orderBy('machine_number')
            ->get(['id', 'name', 'machine_number'])
            ->map(fn (Machine $machine): array => [
                'value' => $machine->id,
                'label' => $machine->machine_number,
            ])
            ->all();
    }

    public function employees(?string $role = null): array
    {
        return User::query()
            ->where('status', Status::ACTIVE->value)
            ->when($role, fn ($query) => $query->where('role', $role))
            ->orderBy('employee_code')
            ->get(['id', 'name', 'employee_code', 'role'])
            ->map(fn (User $user): array => [
                'value' => $user->id,
                'label' => $user->employee_code.' - '.$user->name,
            ])
            ->all();
    }

    /**
     * Get active tools.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function tools(?string $type = null): array
    {
        return Tool::query()
            ->where('status', Status::ACTIVE->value)
            ->when(
                $type,
                fn ($query) => $query->where('type', $type)
            )
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (Tool $tool): array => [
                'value' => $tool->id,
                'label' => $tool->name,
            ])
            ->all();
    }
}
