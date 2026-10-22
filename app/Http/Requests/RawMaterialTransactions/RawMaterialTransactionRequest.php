<?php

namespace App\Http\Requests\RawMaterialTransactions;

use App\Enums\RawMaterialTransactionType;
use App\Models\BandsawWorkLog;
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
                'max:4294967295',
                Rule::when(
                    $this->input('type') === RawMaterialTransactionType::ADJUSTMENT->value,
                    ['not_in:0', 'min:-2147483648'],
                    ['min:1'],
                ),
            ],
            'reference_type' => [
                'nullable',
                'required_with:reference_id',
                'string',
                Rule::in([
                    BandsawWorkLog::class,
                    'bandsaw_work_log',
                ]),
            ],
            'reference_id' => [
                'nullable',
                'required_with:reference_type',
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
