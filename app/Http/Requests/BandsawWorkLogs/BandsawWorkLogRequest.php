<?php

namespace App\Http\Requests\BandsawWorkLogs;

use App\Enums\UserRole;
use App\Models\Part;
use App\Models\SectionSize;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BandsawWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
                Rule::requiredIf(
                    fn () => $this->user()->role !== UserRole::BANDSAW,
                ),
            ],

            'work_date' => [
                'required',
                'date',
            ],

            'part_id' => [
                'required',
                'integer',
                Rule::exists(Part::class, 'id'),
            ],

            'section_size_id' => [
                'required',
                'integer',
                Rule::exists(
                    SectionSize::class,
                    'id',
                ),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
