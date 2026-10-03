<script setup lang="ts">
import { Search } from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Model
|--------------------------------------------------------------------------
*/

const model = defineModel<string>({
    default: '',
});

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    search: [];
}>();

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        emit('search');
    }
};
</script>

<template>
    <AppInput
        v-model="model"
        type="search"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :autofocus="autofocus"
        :autocomplete="autocomplete"
        :name="name"
        :id="id"
        :icon="Search"
        @keydown="handleKeydown"
    />
</template>
