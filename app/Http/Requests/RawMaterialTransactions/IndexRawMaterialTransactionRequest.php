<?php

namespace App\Http\Requests\RawMaterialTransactions;

use App\Enums\RawMaterialTransactionType;
use App\Http\Requests\Concerns\IndexQueryRules;
use App\Models\Section;
use App\Models\SectionSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRawMaterialTransactionRequest extends FormRequest
{
    use IndexQueryRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->indexQueryRules([
                'id',
                'sectionSize.section.name',
                'sectionSize.size',
                'type',
                'quantity',
                'user.name',
                'created_at',
            ]),

            'section_id' => [
                'nullable',
                'integer',
                Rule::exists(Section::class, 'id'),
            ],

            'section_size_id' => [
                'nullable',
                'integer',
                Rule::exists(SectionSize::class, 'id'),
            ],

            'type' => [
                'nullable',
                Rule::enum(RawMaterialTransactionType::class),
            ],

            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],
        ];
    }
}
