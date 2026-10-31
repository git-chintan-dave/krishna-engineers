<script lang="ts" setup>
import {onMounted, ref, watch} from 'vue';
import {RefreshCw} from '@lucide/vue';

import AppSelect from '@/components/common/AppSelect.vue';
import {useLookup} from '@/composables/useLookup';
import type {SelectOption} from '@/types/common';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type PartSelectOption = SelectOption<number> & {
    cutting_size: string | null;
    section_id: number | null;
};

type AppSelectValue = string | number | null | (string | number)[];

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
        showRefresh?: boolean;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select part...',
        searchable: true,
        clearable: false,
        disabled: false,
        error: '',
        showRefresh: true,
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
    part: [part: PartSelectOption | null];
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const options = ref<PartSelectOption[]>([]);
const loading = ref(false);
const refreshing = ref(false);

const { parts, clearLookupCache } = useLookup();

let requestId = 0;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const normalizeValue = (value: unknown): number | null => {
    if (typeof value === 'number') {
        return value;
    }

    if (typeof value === 'string' && value.trim() !== '') {
        const numberValue = Number(value);

        return Number.isFinite(numberValue) ? numberValue : null;
    }

    return null;
};

const getPart = (value: number | null): PartSelectOption | null => {
    if (value === null) {
        return null;
    }

    return options.value.find((option) => option.value === value) ?? null;
};

const emitSelectedPart = (value: number | null): void => {
    emit('part', getPart(value));
};

/*
|--------------------------------------------------------------------------
| Value Changes
|--------------------------------------------------------------------------
*/

const handleModelValue = (value: AppSelectValue): void => {
    const partId = normalizeValue(value);

    emit('update:modelValue', partId);
    emitSelectedPart(partId);
};

const handleChange = (value: AppSelectValue): void => {
    const partId = normalizeValue(value);

    emit('change', partId);
};

/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/

const loadOptions = async (): Promise<void> => {
    const currentRequestId = ++requestId;

    loading.value = true;

    try {
        const result = await parts({
            cache: 'memory',
        });

        // Ignore outdated requests.
        if (currentRequestId !== requestId) {
            return;
        }

        options.value = result as PartSelectOption[];

        // Resolve the selected part after options are loaded.
        emitSelectedPart(props.modelValue);
    } catch (error) {
        if (currentRequestId !== requestId) {
            return;
        }

        console.error('Failed to load parts:', error);
        options.value = [];
        emitSelectedPart(props.modelValue);
    } finally {
        if (currentRequestId === requestId) {
            loading.value = false;
        }
    }
};

/*
|--------------------------------------------------------------------------
| Refresh Options
|--------------------------------------------------------------------------
*/

const refreshOptions = async (): Promise<void> => {
    if (props.disabled || loading.value || refreshing.value) {
        return;
    }

    refreshing.value = true;

    try {
        // Invalidate the existing parts lookup cache.
        clearLookupCache('parts');

        // Fetch fresh data and cache it in memory.
        await loadOptions();
    } finally {
        refreshing.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(loadOptions);

/*
|--------------------------------------------------------------------------
| Programmatic Model Value Changes
|--------------------------------------------------------------------------
*/

watch(
    () => props.modelValue,
    (value) => {
        if (!loading.value) {
            emitSelectedPart(value);
        }
    },
);
</script>

<template>
    <div class="flex items-start gap-1.5">
        <div class="min-w-0 flex-1">
            <AppSelect
                :clearable="clearable"
                :disabled="disabled"
                :error="error"
                :loading="loading"
                :model-value="modelValue"
                :options="options"
                :placeholder="placeholder"
                :searchable="searchable"
                @change="handleChange"
                @update:model-value="handleModelValue"
            />
        </div>

        <button
            v-if="showRefresh"
            type="button"
            class="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-input text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
            :disabled="disabled || loading || refreshing"
            title="Refresh parts"
            aria-label="Refresh parts"
            @click="refreshOptions"
        >
            <RefreshCw
                class="size-4"
                :class="{ 'animate-spin': refreshing }"
            />
        </button>
    </div>
</template>
