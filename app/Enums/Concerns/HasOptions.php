<?php

namespace App\Enums\Concerns;

trait HasOptions
{
    /**
     * Get all enum cases as select options.
     *
     * @return array<int, array{value: string|int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn(self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases(),
        );
    }

    /**
     * Get the human-readable label for the enum case.
     */
    abstract public function label(): string;
}
