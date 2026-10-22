<?php

namespace App\Http\Requests\ToolTransactions;

use App\Enums\ToolTransactionType;
use App\Http\Requests\Concerns\IndexQueryRules;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexToolTransactionRequest extends FormRequest
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
                'id',
                'tool.name',
                'type',
                'quantity',
                'fromUser.name',
                'toUser.name',
                'part.name',
                'setup.name',
                'return_scrap',
                'created_at',
            ]),

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
        ];
    }
}
