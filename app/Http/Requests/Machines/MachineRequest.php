<?php

namespace App\Http\Requests\Machines;

use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $machine = $this->route('machine');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'machine_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('machines', 'machine_number')
                    ->ignore($machine),
            ],

            'status' => [
                'required',
                Rule::enum(Status::class),
            ],
        ];
    }
}
