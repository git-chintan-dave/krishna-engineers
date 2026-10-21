<?php

namespace App\Http\Requests\SectionSizes;

use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSectionSizeRequest extends FormRequest
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
                'size',
                'weight',
                'quantity',
                'created_at',
                'section.name',
            ]),

            'section_id' => [
                'nullable',
                'integer',
                Rule::exists('sections', 'id'),
            ],
        ];
    }
}
