<script lang="ts" setup>
import {onMounted, ref, watch} from 'vue';
import {RefreshCw} from '@lucide/vue';

import AppSelect from '@/components/common/AppSelect.vue';
import {useLookup} from '@/composables/useLookup';
import type {SelectOption} from '@/types/common';
import type {AppSelectValue, NumericSelectValue,} from '@/utils/selectUtils';
import {isValidSelectionMode, normalizeSelectValue,} from '@/utils/selectUtils';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type PartSelectOption = SelectOption<number> & {
    cutting_size: string | null;
    section_id: number | null;
};

type SelectValue = NumericSelectValue;

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: SelectValue;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
        multiple?: boolean;
        showRefresh?: boolean;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select part...',
        searchable: true,
        clearable: false,
        disabled: false,
        error: '',
        multiple: false,
        showRefresh: true,
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    'update:modelValue': [value: SelectValue];
    change: [value: SelectValue];
    part: [part: PartSelectOption | PartSelectOption[] | null];
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

const getPart = (value: number | null): PartSelectOption | null => {
    if (value === null) {
        return null;
    }

    return options.value.find((option) => option.value === value) ?? null;
};

const getSelectedParts = (
    value: SelectValue,
): PartSelectOption | PartSelectOption[] | null => {
    if (Array.isArray(value)) {
        return value
            .map((id) => getPart(id))
            .filter((part): part is PartSelectOption => part !== null);
    }

    return getPart(value);
};

const emitSelectedPart = (value: SelectValue): void => {
    emit('part', getSelectedParts(value));
};

/*
|--------------------------------------------------------------------------
| Value Handling
|--------------------------------------------------------------------------
*/

const handleModelValue = (value: AppSelectValue): void => {
    if (!isValidSelectionMode(value, props.multiple)) {
        return;
    }

    const normalizedValue = normalizeSelectValue(value);

    emit('update:modelValue', normalizedValue);
    emitSelectedPart(normalizedValue);
};

const handleChange = (value: AppSelectValue): void => {
    if (!isValidSelectionMode(value, props.multiple)) {
        return;
    }

    emit('change', normalizeSelectValue(value));
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

        // Resolve selected parts after options are loaded.
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
                :disabled="disabled || loading || refreshing"
                :error="error"
                :loading="loading"
                :model-value="modelValue"
                :multiple="multiple"
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
