<?php

namespace App\Http\Requests\Tools;

use App\Enums\Status;
use App\Enums\ToolType;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToolRequest extends FormRequest
{
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
        $tool = $this->route('tool');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tools', 'name')
                    ->where(
                        fn(Builder $query): Builder => $query->where(
                            'type',
                            $this->input('type'),
                        ),
                    )
                    ->ignore($tool),
            ],

            'type' => [
                'required',
                Rule::enum(ToolType::class),
            ],

            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_image' => [
                'boolean',
            ],

            'status' => [
                'required',
                Rule::enum(Status::class),
            ],
        ];
    }
}
