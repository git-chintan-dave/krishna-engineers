<?php

namespace App\Http\Requests\OperatorWorkLogs;

use App\Enums\UserShift;
use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOperatorWorkLogRequest extends FormRequest
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
                'work_date',
                'shift',
                'user_id',
                'machine_id',
                'part_id',
                'setup_id',
                'quantity',
                'created_at',
            ]),

            'work_date' => [
                'nullable',
                'date',
            ],

            'shift' => [
                'nullable',
                Rule::enum(UserShift::class),
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'machine_id' => [
                'nullable',
                'integer',
                'exists:machines,id',
            ],

            'part_id' => [
                'nullable',
                'integer',
                'exists:parts,id',
            ],

            'setup_id' => [
                'nullable',
                'integer',
                'exists:setups,id',
            ],
        ];
    }
}
