<?php

namespace App\Http\Requests\Parts;

use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $part = $this->route('part');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'new_part_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('parts', 'new_part_name')
                    ->ignore($part),
            ],

            'section_id' => [],

            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_image' => [
                'boolean',
            ],

            'cutting_size' => [
                'nullable',
                'string',
                'max:255',
            ],

            'part_weight' => [
                'nullable',
                'string',
                'max:255',
            ],

            'raw_material_weight' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::enum(Status::class),
            ],
        ];
    }
}
