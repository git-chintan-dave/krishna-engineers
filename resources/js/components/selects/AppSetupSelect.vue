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
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: NumericSelectValue;
        partId?: number | number[] | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
        showRefresh?: boolean;
        multiple?: boolean;
    }>(),
    {
        modelValue: null,
        partId: null,
        placeholder: 'Select setup...',
        searchable: false,
        clearable: false,
        disabled: false,
        error: '',
        showRefresh: true,
        multiple: false,
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    'update:modelValue': [value: NumericSelectValue];
    change: [value: NumericSelectValue];
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const options = ref<SelectOption<number>[]>([]);
const loading = ref(false);
const refreshing = ref(false);

const { setups, clearLookupCache } = useLookup();

let requestId = 0;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getPartIds = (): number[] => {
    const ids = Array.isArray(props.partId)
        ? props.partId
        : props.partId
            ? [props.partId]
            : [];

    return [...new Set(ids.filter((id) => Number.isFinite(id) && id > 0))];
};

/*
|--------------------------------------------------------------------------
| Fetch Setups
|--------------------------------------------------------------------------
*/

const fetchSetups = async (): Promise<void> => {
    const currentRequestId = ++requestId;
    const partIds = getPartIds();

    options.value = [];

    if (!partIds.length) {
        loading.value = false;
        return;
    }

    loading.value = true;

    try {
        const result = await setups(
            partIds.length === 1 ? partIds[0] : partIds,
            {
                cache: 'memory',
            },
        );

        // Ignore outdated requests.
        if (currentRequestId !== requestId) {
            return;
        }

        options.value = result;
    } catch (error) {
        if (currentRequestId !== requestId) {
            return;
        }

        console.error('Failed to load setups:', error);

        options.value = [];
    } finally {
        if (currentRequestId === requestId) {
            loading.value = false;
        }
    }
};

/*
|--------------------------------------------------------------------------
| Refresh Setups
|--------------------------------------------------------------------------
*/

const refreshSetups = async (): Promise<void> => {
    const partIds = getPartIds();

    if (
        props.disabled ||
        !partIds.length ||
        loading.value ||
        refreshing.value
    ) {
        return;
    }

    refreshing.value = true;

    try {
        // Invalidate only the current part IDs' setups cache.
        clearLookupCache(`setups:part_id=${partIds.join(',')}`);

        // Fetch fresh data and cache it in memory.
        await fetchSetups();
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
    // Ensure the emitted value matches the selection mode.
    if (!isValidSelectionMode(value, props.multiple)) {
        return;
    }

    const normalizedValue: NumericSelectValue =
        normalizeSelectValue(value);

    emit('update:modelValue', normalizedValue);
    emit('change', normalizedValue);
};

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(
    () => props.partId,
    fetchSetups,
    { immediate: true, deep: true },
);
</script>

<template>
    <div class="flex items-start gap-1.5">
        <div class="min-w-0 flex-1">
            <AppSelect
                :clearable="clearable"
                :disabled="
                    disabled ||
                    loading ||
                    refreshing ||
                    !getPartIds().length
                "
                :error="error"
                :loading="loading"
                :model-value="modelValue"
                :multiple="multiple"
                :options="options"
                :placeholder="placeholder"
                :searchable="searchable"
                @update:model-value="handleUpdate"
            />
        </div>

        <button
            v-if="showRefresh"
            type="button"
            class="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-input text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
            :disabled="
                disabled ||
                !getPartIds().length ||
                loading ||
                refreshing
            "
            title="Refresh setups"
            aria-label="Refresh setups"
            @click="refreshSetups"
        >
            <RefreshCw
                class="size-4"
                :class="{ 'animate-spin': refreshing }"
            />
        </button>
    </div>
</template>
