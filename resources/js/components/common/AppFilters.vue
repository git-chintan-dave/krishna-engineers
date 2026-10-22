<script lang="ts" setup>
import { Search } from '@lucide/vue';
import { useAttrs } from 'vue';

import { Input } from '@/components/ui/input';

defineOptions({
    inheritAttrs: false,
});

// Props
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

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: string];
    search: [];
}>();

// Attributes
const attrs = useAttrs();

// Actions
const handleInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
};

const handleSearch = () => {
    emit('search');
};
</script>

<template>
    <div class="relative w-full min-w-0">
        <Search
            aria-hidden="true"
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />

        <Input
            :disabled="disabled"
            :placeholder="placeholder"
            :value="modelValue"
            aria-label="Search"
            class="h-9 w-full min-w-0 pl-9"
            type="search"
            v-bind="attrs"
            @input="handleInput"
            @keyup.enter="handleSearch"
        />
    </div>
</template>
