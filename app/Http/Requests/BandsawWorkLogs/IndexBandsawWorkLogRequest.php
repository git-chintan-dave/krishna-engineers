<?php

namespace App\Http\Requests\BandsawWorkLogs;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\IndexQueryRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBandsawWorkLogRequest extends FormRequest
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
                'user_id',
                'part_id',
                'section_id',
                'section_size_id',
                'quantity',
                'created_at',
                'user.name',
                'part.name',
                'section.name',
                'section_size.size',
            ]),

            'work_date' => [
                'nullable',
                'date',
            ],

            'user_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    User::class,
                    'id',
                )->where(
                    'role',
                    UserRole::BANDSAW->value,
                ),
            ],

            'part_id' => [
                'nullable',
                'integer',
                'exists:parts,id',
            ],

            'section_id' => [
                'nullable',
                'integer',
                'exists:sections,id',
            ],

            'section_size_id' => [
                'nullable',
                'integer',
                'exists:section_sizes,id',
            ],
        ];
    }
}
