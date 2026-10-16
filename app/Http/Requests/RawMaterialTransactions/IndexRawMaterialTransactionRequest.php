<?php

namespace App\Http\Requests\RawMaterialTransactions;

use App\Enums\RawMaterialTransactionType;
use App\Models\SectionSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRawMaterialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
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
            'from_date' => [
                'nullable',
                'date',
            ],
            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],
            'sort' => [
                'required',
                Rule::in([
                    'created_at',
                    'quantity',
                    'type',
                ]),
            ],
            'direction' => [
                'required',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
            'per_page' => [
                'required',
                'integer',
                Rule::in([10, 25, 50, 100]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->input('search', ''),
            'sort' => $this->input('sort', 'created_at'),
            'direction' => $this->input('direction', 'desc'),
            'per_page' => $this->input('per_page', 10),
        ]);
    }
}
