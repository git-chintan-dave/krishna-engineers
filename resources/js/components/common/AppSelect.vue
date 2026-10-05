<script setup lang="ts">
import {computed, useAttrs,} from 'vue';
import {Check, ChevronsUpDown, X,} from '@lucide/vue';

import {Button} from '@/components/ui/button';
import {Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList,} from '@/components/ui/command';
import {Popover, PopoverContent, PopoverTrigger,} from '@/components/ui/popover';

import type {SelectOption as CommonSelectOption} from '@/types/common';

defineOptions({
    inheritAttrs: false,
});

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type Primitive = string | number;

type Option =
    | Primitive
    | (CommonSelectOption<Primitive> & {
    [key: string]: unknown;
});

type ModelValue =
    | Primitive
    | Primitive[]
    | null;

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: ModelValue;
        options: Option[];

        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;

        searchable?: boolean;
        multiple?: boolean;
        clearable?: boolean;
        required?: boolean;
        disabled?: boolean;
        loading?: boolean;

        optionLabel?: string;
        optionValue?: string;

        error?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select option...',
        searchPlaceholder: 'Search...',
        emptyText: 'No options found.',

        searchable: false,
        multiple: false,
        clearable: false,
        required: false,
        disabled: false,
        loading: false,

        optionLabel: 'label',
        optionValue: 'value',

        error: '',
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    'update:modelValue': [value: ModelValue];
    change: [value: ModelValue];
}>();

/*
|--------------------------------------------------------------------------
| Attributes
|--------------------------------------------------------------------------
*/

const attrs = useAttrs();

const inputName = computed(
    () => attrs.name as string | undefined,
);

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const open = defineModel<boolean>('open', {
    default: false,
});

/*
|--------------------------------------------------------------------------
| Options
|--------------------------------------------------------------------------
*/

const normalizedOptions = computed(() =>
    props.options.map((option) => {
        if (
            typeof option === 'string' ||
            typeof option === 'number'
        ) {
            return {
                label: String(option),
                value: option,
            };
        }

        const value =
            (option[
                props.optionValue
                ] as Primitive | undefined) ??
            option.value ??
            null;

        const label =
            option[props.optionLabel] ??
            option.label ??
            value ??
            '';

        return {
            label: String(label),
            value,
        };
    }),
);

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

const selectedValues = computed<Primitive[]>(
    () => {
        if (props.multiple) {
            return Array.isArray(
                props.modelValue,
            )
                ? props.modelValue
                : [];
        }

        if (
            props.modelValue === null ||
            props.modelValue === undefined
        ) {
            return [];
        }

        return [
            props.modelValue as Primitive,
        ];
    },
);

const selectedOptions = computed(() =>
    normalizedOptions.value.filter(
        (option) =>
            selectedValues.value.some(
                (value) =>
                    String(value) ===
                    String(option.value),
            ),
    ),
);

const selectedOption = computed(
    () => selectedOptions.value[0],
);

const selectedLabel = computed(
    () => selectedOption.value?.label ?? '',
);

const hasSelection = computed(
    () => selectedValues.value.length > 0,
);

const isSelected = (
    value: Primitive,
) =>
    selectedValues.value.some(
        (selectedValue) =>
            String(selectedValue) ===
            String(value),
    );

/*
|--------------------------------------------------------------------------
| Native Validation
|--------------------------------------------------------------------------
*/

const nativeSelectValue = computed(() => {
    if (props.multiple) {
        return selectedValues.value.map(
            (value) => String(value),
        );
    }

    return hasSelection.value
        ? String(selectedValues.value[0])
        : '';
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const emitValue = (
    value: ModelValue,
) => {
    emit(
        'update:modelValue',
        value,
    );

    emit('change', value);
};

const selectOption = (
    value: Primitive,
) => {
    if (props.multiple) {
        const currentValues = [
            ...selectedValues.value,
        ];

        const index =
            currentValues.findIndex(
                (selectedValue) =>
                    String(
                        selectedValue,
                    ) === String(value),
            );

        if (index >= 0) {
            currentValues.splice(index, 1);
        } else {
            currentValues.push(value);
        }

        emitValue(currentValues);

        return;
    }

    emitValue(
        isSelected(value)
            ? null
            : value,
    );

    open.value = false;
};

const clearSelection = () => {
    emitValue(
        props.multiple ? [] : null,
    );
};
</script>

<template>
    <div class="w-full min-w-0">
        <!--
            Native select is used only for browser
            form validation.

        The visible select is a custom Popover,
        therefore native required validation cannot
        be applied directly to the Button.
    -->
        <select
            v-if="required"
            :name="inputName"
            :required="required"
            :disabled="
            disabled || loading
        "
            :multiple="multiple"
            :value="nativeSelectValue"
            tabindex="-1"
            aria-hidden="true"
            class="sr-only"
        >
            <option
                v-if="!multiple"
                value=""
            >
                {{ placeholder }}
            </option>

            <option
                v-for="option in normalizedOptions"
                :key="
                String(option.value)
            "
                :value="
                String(option.value)
            "
            >
                {{ option.label }}
            </option>
        </select>

        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    :aria-invalid="!!error"
                    :aria-required="required"
                    :disabled="
                    disabled || loading
                "
                    class="h-9 w-full min-w-0 justify-between font-normal"
                    :class="
                    error &&
                    'border-destructive focus-visible:ring-destructive'
                "
                    v-bind="attrs"
                    :name="undefined"
                >
                    <!-- Selected Value -->
                    <span
                        class="min-w-0 flex-1 truncate text-left"
                        :class="[
                        !hasSelection &&
                            'text-muted-foreground',
                    ]"
                    >
                    <!-- Loading -->
                    <template
                        v-if="loading"
                    >
                        Loading...
                    </template>

                        <!-- Multiple -->
                    <template
                        v-else-if="
                            multiple &&
                            selectedOptions.length
                        "
                    >
                        <span
                            class="truncate"
                        >
                            {{
                                selectedOptions[0]
                                    ?.label
                            }}
                        </span>

                        <span
                            v-if="
                                selectedOptions.length >
                                1
                            "
                            class="ml-1 shrink-0 text-xs text-muted-foreground"
                        >
                            +{{
                                selectedOptions.length -
                                1
                            }}
                        </span>
                    </template>

                        <!-- Single / Empty -->
                    <template
                        v-else
                    >
                        {{
                            selectedLabel ||
                            placeholder
                        }}
                    </template>
                </span>

                    <!-- Actions -->
                    <span
                        class="ml-2 flex shrink-0 items-center gap-1"
                    >
                    <!-- Clear -->
                    <span
                        v-if="
                            clearable &&
                            hasSelection &&
                            !loading
                        "
                        role="button"
                        tabindex="0"
                        aria-label="Clear selection"
                        class="flex size-7 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        @click.stop="
                            clearSelection
                        "
                        @keydown.enter.stop="
                            clearSelection
                        "
                        @keydown.space.prevent.stop="
                            clearSelection
                        "
                    >
                        <X
                            class="size-3.5"
                            aria-hidden="true"
                        />
                    </span>

                        <!-- Dropdown Indicator -->
                    <ChevronsUpDown
                        class="size-4 shrink-0 text-muted-foreground opacity-70"
                        aria-hidden="true"
                    />
                </span>
                </Button>
            </PopoverTrigger>

            <PopoverContent
                align="start"
                class="w-(--reka-popover-trigger-width) max-w-[calc(100vw-2rem)] min-w-0 p-0"
            >
                <Command class="w-full min-w-0">
                    <!-- Search -->
                    <CommandInput
                        v-if="searchable"
                        :placeholder="
                        searchPlaceholder
                    "
                        class="h-9"
                    />

                    <CommandList
                        class="max-h-60 overflow-x-hidden"
                    >
                        <!-- Empty -->
                        <CommandEmpty>
                            {{ emptyText }}
                        </CommandEmpty>

                        <!-- Options -->
                        <CommandGroup>
                            <CommandItem
                                v-for="option in normalizedOptions"
                                :key="
                                String(
                                    option.value,
                                )
                            "
                                :value="
                                option.label
                            "
                                class="min-w-0 cursor-pointer"
                                @select="
                                selectOption(
                                    option.value as Primitive,
                                )
                            "
                            >
                            <span
                                class="min-w-0 flex-1 truncate"
                            >
                                {{
                                    option.label
                                }}
                            </span>

                                <Check
                                    :class="[
                                    'ml-3 size-4 shrink-0',
                                    isSelected(
                                        option.value as Primitive,
                                    )
                                        ? 'opacity-100'
                                        : 'opacity-0',
                                ]"
                                    aria-hidden="true"
                                />
                            </CommandItem>
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>

        <!-- Error -->
        <p
            v-if="error"
            class="mt-1.5 text-xs text-destructive"
        >
            {{ error }}
        </p>
    </div>
</template>
