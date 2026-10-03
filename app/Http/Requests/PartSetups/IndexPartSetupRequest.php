<?php

namespace App\Http\Requests\PartSetups;

use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;

class IndexPartSetupRequest extends FormRequest
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
                'part',
                'setup',
                'part_id',
                'setup_id',
                'machine_cycle_time_seconds',
                'actual_cycle_time_seconds',
                'created_at',
            ]),

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
