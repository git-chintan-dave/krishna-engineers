<?php

namespace App\Http\Requests\Sections;

use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $section = $this->route('section');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')
                    ->ignore($section),
            ],

            'status' => [
                'required',
                Rule::enum(Status::class),
            ],
        ];
    }
}
