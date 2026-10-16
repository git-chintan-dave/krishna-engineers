<?php

namespace App\Http\Requests\SectionSizes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sectionSize = $this->route('section_size');

        return [
            'section_id' => [
                'required',
                'integer',
                Rule::exists('sections', 'id'),
            ],

            'size' => [
                'required',
                'string',
                'max:255',
                Rule::unique('section_sizes', 'size')
                    ->where(
                        fn($query) => $query->where(
                            'section_id',
                            $this->input('section_id'),
                        ),
                    )
                    ->ignore($sectionSize),
            ],

            'weight' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
