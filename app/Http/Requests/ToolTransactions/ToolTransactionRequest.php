<?php

namespace App\Http\Requests\ToolTransactions;

use App\Enums\ToolTransactionType;
use App\Models\Part;
use App\Models\Setup;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToolTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tool_id' => [
                'required',
                'integer',
                Rule::exists(Tool::class, 'id'),
            ],

            'type' => [
                'required',
                Rule::enum(ToolTransactionType::class),
            ],

            'quantity' => [
                'required',
                'integer',
                'max:4294967295',
                Rule::when(
                    $this->input('type') === ToolTransactionType::ADJUSTMENT->value,
                    ['not_in:0', 'min:-2147483648'],
                    ['min:1'],
                ),
            ],

            'from_user_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id'),
                'different:to_user_id',
            ],

            'to_user_id' => [
                'required_if:type,' . ToolTransactionType::ISSUE->value,
                'nullable',
                'integer',
                Rule::exists(User::class, 'id'),
                'different:from_user_id',
            ],

            'part_id' => [
                'nullable',
                'integer',
                Rule::exists(Part::class, 'id'),
            ],

            'setup_id' => [
                'nullable',
                'integer',
                Rule::exists(Setup::class, 'id'),
            ],

            'return_scrap' => [
                'nullable',
                'boolean',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
