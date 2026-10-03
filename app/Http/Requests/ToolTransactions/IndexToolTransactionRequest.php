<?php

namespace App\Http\Requests\ToolTransactions;

use App\Enums\ToolTransactionType;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexToolTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'tool_id' => [
                'nullable',
                'integer',
                Rule::exists(Tool::class, 'id'),
            ],

            'type' => [
                'nullable',
                Rule::enum(ToolTransactionType::class),
            ],

            'from_user_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id'),
            ],

            'to_user_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id'),
            ],

            'part_id' => [
                'nullable',
                'integer',
                Rule::exists('parts', 'id'),
            ],

            'setup_id' => [
                'nullable',
                'integer',
                Rule::exists('setups', 'id'),
            ],

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],

            'sort' => [
                'nullable',
                'string',
                Rule::in([
                    'created_at',
                    'quantity',
                    'type',
                ]),
            ],

            'direction' => [
                'nullable',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
