<?php

namespace App\Http\Requests\PartSetups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partSetup = $this->route('part_setup');

        return [
            'part_id' => [
                'required',
                'integer',
                'exists:parts,id',
            ],

            'setup_id' => [
                'required',
                'integer',
                'exists:setups,id',
                Rule::unique('part_setups', 'setup_id')
                    ->where(
                        fn ($query) => $query->where(
                            'part_id',
                            $this->input('part_id'),
                        ),
                    )
                    ->ignore($partSetup),
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

            'machine_cycle_time' => [
                'required',
                'integer',
                'min:0',
            ],

            'actual_cycle_time' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
