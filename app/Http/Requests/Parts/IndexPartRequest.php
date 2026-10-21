<?php

namespace App\Http\Requests\Parts;

use App\Enums\Status;
use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPartRequest extends FormRequest
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
                'name',
                'new_part_name',
                'cutting_size',
                'part_weight',
                'raw_material_weight',
                'status',
                'created_at',
                'section.name',
            ]),

            'section_id' => [
                'nullable',
                'integer',
                Rule::exists('sections', 'id'),
            ],

            'status' => [
                'nullable',
                Rule::enum(Status::class),
            ],
        ];
    }
}
