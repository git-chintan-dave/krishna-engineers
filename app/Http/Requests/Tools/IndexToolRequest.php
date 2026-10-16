<?php

namespace App\Http\Requests\Tools;

use App\Enums\Status;
use App\Enums\ToolType;
use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexToolRequest extends FormRequest
{
    use IndexQueryRules;

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return [
            ...$this->indexQueryRules([
                'name',
                'type',
                'total_quantity',
                'status',
                'created_at',
            ]),

            'type' => [
                'nullable',
                Rule::enum(ToolType::class),
            ],

            'status' => [
                'nullable',
                Rule::enum(Status::class),
            ],
        ];
    }
}
