<?php

namespace App\Http\Requests\Machines;

use App\Enums\Status;
use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMachineRequest extends FormRequest
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
                'reference_name',
                'status',
                'created_at',
            ]),

            'status' => [
                'nullable',
                Rule::enum(Status::class),
            ],
        ];
    }
}
