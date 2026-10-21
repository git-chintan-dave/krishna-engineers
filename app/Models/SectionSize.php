<?php

namespace App\Models;

use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'section_id',
    'size',
    'weight',
    'quantity',
])]
class SectionSize extends Model
{
    use HasFilter, HasUuids;

    protected array $searchable = [
        'size',
        'weight',
        'quantity',
    ];

    protected array $filterable = [
        'section_id',
        'section.name',
    ];

    protected array $sortable = [
        'id',
        'size',
        'weight',
        'quantity',
        'created_at',
    ];

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'desc';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relationships
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function rawMaterialTransactions(): HasMany
    {
        return $this->hasMany(RawMaterialTransaction::class);
    }
}
