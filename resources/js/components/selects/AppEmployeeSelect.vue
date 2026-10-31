<script lang="ts" setup>
import {ref, watch} from 'vue';
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

type EmployeeRole =
    | 'super_admin'
    | 'supervisor'
    | 'operator'
    | 'bandsaw'
    | 'deburring_packaging';

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
        role?: EmployeeRole | null;
        multiple?: boolean;
        showRefresh?: boolean;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select employee',
        searchable: false,
        clearable: false,
        disabled: false,
        error: undefined,
        role: null,
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
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const options = ref<SelectOption<number>[]>([]);
const loading = ref(false);
const refreshing = ref(false);

const { employees, clearLookupCache } = useLookup();

let requestId = 0;

/*
|--------------------------------------------------------------------------
| Fetch Employees
|--------------------------------------------------------------------------
*/

const fetchEmployees = async (): Promise<void> => {
    const currentRequestId = ++requestId;

    loading.value = true;

    try {
        const result = await employees(props.role, {
            cache: 'memory',
        });

        // Ignore outdated requests.
        if (currentRequestId !== requestId) {
            return;
        }

        options.value = result;
    } catch (error) {
        if (currentRequestId !== requestId) {
            return;
        }

        console.error('Failed to load employees:', error);

        options.value = [];
    } finally {
        if (currentRequestId === requestId) {
            loading.value = false;
        }
    }
};

/*
|--------------------------------------------------------------------------
| Refresh Employees
|--------------------------------------------------------------------------
*/

const refreshEmployees = async (): Promise<void> => {
    if (props.disabled || loading.value || refreshing.value) {
        return;
    }

    refreshing.value = true;

    try {
        // Build the cache key for the current role.
        const cacheKey = props.role
            ? `employees:role=${encodeURIComponent(props.role)}`
            : 'employees:';

        // Invalidate only the current employee lookup cache.
        clearLookupCache(cacheKey);

        // Fetch fresh data and cache it in memory.
        await fetchEmployees();
    } finally {
        refreshing.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Value Handling
|--------------------------------------------------------------------------
*/

const handleUpdate = (value: AppSelectValue): void => {
    if (!isValidSelectionMode(value, props.multiple)) {
        return;
    }

    emit('update:modelValue', normalizeSelectValue(value));
};

const handleChange = (value: AppSelectValue): void => {
    if (!isValidSelectionMode(value, props.multiple)) {
        return;
    }

    emit('change', normalizeSelectValue(value));
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

watch(
    () => props.role,
    fetchEmployees,
    { immediate: true },
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
                @update:model-value="handleUpdate"
            />
        </div>

        <button
            v-if="showRefresh"
            type="button"
            class="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-input text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
            :disabled="disabled || loading || refreshing"
            title="Refresh employees"
            aria-label="Refresh employees"
            @click="refreshEmployees"
        >
            <RefreshCw
                class="size-4"
                :class="{ 'animate-spin': refreshing }"
            />
        </button>
    </div>
</template>
