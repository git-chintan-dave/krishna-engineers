<?php

namespace App\Http\Requests\BandsawWorkLogs;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBandsawWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->input('search', ''),
            'sort' => $this->input('sort', 'work_date'),
            'direction' => $this->input('direction', 'desc'),
            'per_page' => $this->input('per_page', 10),
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'work_date' => [
                'nullable',
                'date',
            ],

            'user_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id')
                    ->where('role', UserRole::BANDSAW->value),
            ],

            'part_id' => [
                'nullable',
                'integer',
                'exists:parts,id',
            ],

            'section_size_id' => [
                'nullable',
                'integer',
                'exists:section_sizes,id',
            ],

            'sort' => [
                'nullable',
                'string',
                Rule::in([
                    'work_date',
                    'quantity',
                    'created_at',
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
                Rule::in([10, 25, 50, 100]),
            ],
        ];
    }
}
