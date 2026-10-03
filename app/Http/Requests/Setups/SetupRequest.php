<?php

namespace App\Http\Requests\Setups;

use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $setup = $this->route('setup');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('setups', 'name')
                    ->ignore($setup),
            ],

            'status' => [
                'required',
                Rule::enum(Status::class),
            ],
        ];
    }
}
