<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait IndexQueryRules
{
    protected function indexQueryRules(array $sortColumns = []): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([10, 25, 50, 100]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'sort' => [
                'nullable',
                'string',
                ...($sortColumns !== [] ? [
                    Rule::in($sortColumns),
                ] : []),
            ],

            'direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],
        ];
    }
}
