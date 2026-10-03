<?php

namespace App\Http\Requests\Setups;

use App\Enums\Status;
use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSetupRequest extends FormRequest
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
