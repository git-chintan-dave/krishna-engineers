<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import type { SelectOption } from '@/types/common';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        partId?: number | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
    }>(),
    {
        modelValue: null,
        partId: null,
        placeholder: 'Select setup...',
        searchable: false,
        clearable: false,
        disabled: false,
        error: '',
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    change: [value: number | null];
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const options = ref<SelectOption<number>[]>([]);
const loading = ref(false);

/*
|--------------------------------------------------------------------------
| Fetch Setups
|--------------------------------------------------------------------------
*/

const fetchSetups = async (): Promise<void> => {
    if (!props.partId) {
        options.value = [];
        return;
    }

    loading.value = true;

    try {
        const params = new URLSearchParams({
            part_id: String(props.partId),
        });

        const response = await fetch(`/lookups/setups?${params.toString()}`);

        if (!response.ok) {
            throw new Error('Failed to fetch setups.');
        }

        const data = await response.json();

        options.value = Array.isArray(data)
            ? data
            : Array.isArray(data.data)
              ? data.data
              : [];
    } catch (error) {
        console.error('Failed to load setups:', error);

        options.value = [];
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Value Handling
|--------------------------------------------------------------------------
*/

const handleUpdate = (
    value: string | number | null | (string | number)[],
): void => {
    if (value === null || typeof value === 'number') {
        emit('update:modelValue', value);

        emit('change', value);
    }
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(fetchSetups);

/*
|--------------------------------------------------------------------------
| Watch Part
|--------------------------------------------------------------------------
*/

watch(
    () => props.partId,
    () => {
        /*
         * Do not clear modelValue here.
         *
         * The parent form may already have a setup_id
         * from the operator's last report.
         */
        fetchSetups();
    },
);
</script>

<template>
    <AppSelect
        :model-value="modelValue"
        :options="options"
        :placeholder="placeholder"
        :searchable="searchable"
        :clearable="clearable"
        :disabled="disabled || loading || !partId"
        :loading="loading"
        :error="error"
        @update:model-value="handleUpdate"
    />
</template>
