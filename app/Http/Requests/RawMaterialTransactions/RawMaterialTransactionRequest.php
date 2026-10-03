<?php

namespace App\Http\Requests\RawMaterialTransactions;

use App\Enums\RawMaterialTransactionType;
use App\Models\SectionSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RawMaterialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_size_id' => [
                'required',
                'integer',
                Rule::exists(SectionSize::class, 'id'),
            ],
            'type' => [
                'required',
                Rule::enum(RawMaterialTransactionType::class),
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'reference_type' => [
                'nullable',
                'string',
                'max:255',
            ],
            'reference_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
