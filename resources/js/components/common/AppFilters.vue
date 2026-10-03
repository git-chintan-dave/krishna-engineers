<script setup lang="ts">
import { Search } from '@lucide/vue';
import { useAttrs } from 'vue';

import { Input } from '@/components/ui/input';

defineOptions({
    inheritAttrs: false,
});

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

withDefaults(
    defineProps<{
        modelValue?: string;
        placeholder?: string;
        disabled?: boolean;
    }>(),
    {
        modelValue: '',
        placeholder: 'Search...',
        disabled: false,
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    'update:modelValue': [value: string];
    search: [];
}>();

/*
|--------------------------------------------------------------------------
| Attributes
|--------------------------------------------------------------------------
*/

const attrs = useAttrs();

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleInput = (
    event: Event,
) => {
    const value = (
        event.target as HTMLInputElement
    ).value;

    emit(
        'update:modelValue',
        value,
    );
};

const handleSearch = () => {
    emit('search');
};
</script>

<template>
    <div class="relative min-w-0 w-full">
        <!-- Search Icon -->
        <Search
            class="pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />

        <!-- Search Input -->
        <Input
            type="search"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            class="h-9 min-w-0 w-full pl-9"
            v-bind="attrs"
            @input="handleInput"
            @keyup.enter="handleSearch"
        />
    </div>
</template>
