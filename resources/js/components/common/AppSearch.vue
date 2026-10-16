<script lang="ts" setup>
import {Search} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';

// Props
withDefaults(
    defineProps<{
        placeholder?: string;
        disabled?: boolean;
        readonly?: boolean;
        autofocus?: boolean;
        autocomplete?: string;
        name?: string;
        id?: string;
    }>(),
    {
        placeholder: 'Search...',
        disabled: false,
        readonly: false,
        autofocus: false,
        autocomplete: 'off',
        name: undefined,
        id: undefined,
    },
);

// Model
const model = defineModel<string>({
    default: '',
});

// Emits
const emit = defineEmits<{
    search: [];
}>();

// Actions
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key !== 'Enter') {
        return;
    }

    emit('search');
};
</script>

<template>
    <AppInput
        :id="id"
        v-model="model"
        :autocomplete="autocomplete"
        :autofocus="autofocus"
        :disabled="disabled"
        :icon="Search"
        :name="name"
        :placeholder="placeholder"
        :readonly="readonly"
        aria-label="Search"
        type="search"
        @keydown="handleKeydown"
    />
</template>
