<script lang="ts" setup>
import {ref, watch} from 'vue';
import {RefreshCw} from '@lucide/vue';

import AppSelect from '@/components/common/AppSelect.vue';
import {useLookup} from '@/composables/useLookup';
import type {SelectOption} from '@/types/common';

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
        showRefresh?: boolean;
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
const refreshing = ref(false);

const { setups, clearLookupCache } = useLookup();

let requestId = 0;

/*
|--------------------------------------------------------------------------
| Fetch Setups
|--------------------------------------------------------------------------
*/

const fetchSetups = async (): Promise<void> => {
    const currentRequestId = ++requestId;

    options.value = [];

    if (!props.partId) {
        loading.value = false;
        return;
    }

    loading.value = true;

    try {
        const result = await setups(props.partId, {
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
    if (
        props.disabled ||
        !props.partId ||
        loading.value ||
        refreshing.value
    ) {
        return;
    }

    refreshing.value = true;

    try {
        // Build the cache key for the current part.
        const cacheKey = `setups:part_id=${props.partId}`;

        // Invalidate only the current part's setups cache.
        clearLookupCache(cacheKey);

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
| Lifecycle & Watchers
|--------------------------------------------------------------------------
*/

watch(
    () => props.partId,
    fetchSetups,
    { immediate: true },
);
</script>

<template>
    <div class="flex items-start gap-1.5">
        <div class="min-w-0 flex-1">
            <AppSelect
                :clearable="clearable"
                :disabled="disabled || loading || !partId"
                :error="error"
                :loading="loading"
                :model-value="modelValue"
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
            :disabled="disabled || !partId || loading || refreshing"
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
