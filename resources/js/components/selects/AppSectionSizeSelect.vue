<script lang="ts" setup>
import {onMounted, ref, watch} from 'vue';
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
        sectionId?: number | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
        showRefresh?: boolean;
    }>(),
    {
        modelValue: null,
        sectionId: null,
        placeholder: 'Select section size...',
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
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const options = ref<SelectOption<number>[]>([]);
const loading = ref(false);
const refreshing = ref(false);

const { sectionSizes, clearLookupCache } = useLookup();

let requestId = 0;

/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/

const loadOptions = async (): Promise<void> => {
    const currentRequestId = ++requestId;
    const sectionId = props.sectionId;

    options.value = [];

    if (!sectionId) {
        loading.value = false;
        return;
    }

    loading.value = true;

    try {
        const result = await sectionSizes(sectionId, {
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

        console.error('Failed to load section sizes:', error);
        options.value = [];
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
    if (
        props.disabled ||
        !props.sectionId ||
        loading.value ||
        refreshing.value
    ) {
        return;
    }

    refreshing.value = true;

    try {
        /*
         * Invalidate the section-size lookup cache.
         *
         * clearLookupCache() without a key clears all database
         * lookup caches. Use a targeted key here once the exact
         * sectionSizes cache-key format is confirmed.
         */
        clearLookupCache();

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
| Watchers
|--------------------------------------------------------------------------
*/

watch(
    () => props.sectionId,
    () => {
        /*
         * Do not clear modelValue here.
         *
         * The parent may already have a section_size_id
         * when loading an existing record or applying defaults.
         */
        loadOptions();
    },
);
</script>

<template>
    <div class="flex items-start gap-1.5">
        <div class="min-w-0 flex-1">
            <AppSelect
                :clearable="clearable"
                :disabled="disabled || loading || !sectionId"
                :error="error"
                :loading="loading"
                :model-value="modelValue"
                :options="options"
                :placeholder="placeholder"
                :searchable="searchable"
                @change="emit('change', $event as number | null)"
                @update:model-value="emit('update:modelValue', $event as number | null)"
            />
        </div>

        <button
            v-if="showRefresh"
            type="button"
            class="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-input text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
            :disabled="disabled || !sectionId || loading || refreshing"
            title="Refresh section sizes"
            aria-label="Refresh section sizes"
            @click="refreshOptions"
        >
            <RefreshCw
                class="size-4"
                :class="{ 'animate-spin': refreshing }"
            />
        </button>
    </div>
</template>
