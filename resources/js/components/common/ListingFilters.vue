<script lang="ts" setup>
import {computed, ref, useId, useSlots} from 'vue';
import {ChevronDown, SlidersHorizontal} from '@lucide/vue';

import AppSearch from '@/components/common/AppSearch.vue';
import {Button} from '@/components/ui/button';

import {ACTION_SHORTCUTS, useActionShortcuts} from '@/composables/useActionShortcuts';

const props = withDefaults(
    defineProps<{
        search?: string;
        searchable?: boolean;
        searchPlaceholder?: string;
        searchButtonLabel?: string;
        clearButtonLabel?: string;
        searchDisabled?: boolean;
        clearDisabled?: boolean;
    }>(),
    {
        search: '',
        searchable: true,
        searchPlaceholder: 'Search...',
        searchButtonLabel: 'Search',
        clearButtonLabel: 'Clear',
        searchDisabled: false,
        clearDisabled: undefined,
    },
);

const emit = defineEmits<{
    'update:search': [value: string];
    search: [];
    clear: [];
}>();

const slots = useSlots();
const panelId = useId();

const searchValue = computed({
    get: () => props.search,
    set: (value: string) => emit('update:search', value),
});

const isClearDisabled = computed(
    () => props.clearDisabled ?? !props.search.trim(),
);

const hasFilters = computed(() => Boolean(slots.filters));

const hasCollapsibleContent = computed(
    () => props.searchable || hasFilters.value,
);

const mobileFiltersOpen = ref(false);

/*
|--------------------------------------------------------------------------
| Keyboard Shortcuts
|--------------------------------------------------------------------------
*/

useActionShortcuts(
    computed(() => ({
        search: {
            handler: () => emit('search'),
            enabled: computed(
                () => props.searchable && !props.searchDisabled,
            ),
        },
        clearSearch: {
            handler: () => emit('clear'),
            enabled: computed(() => !isClearDisabled.value),
        },
    })),
);
</script>

<template>
    <div class="min-w-0 border-y py-3 sm:py-4">
        <!-- Mobile Toggle -->
        <button
            v-if="hasCollapsibleContent"
            :aria-controls="panelId"
            :aria-expanded="mobileFiltersOpen"
            class="flex min-h-11 w-full items-center justify-between gap-3 rounded-lg border bg-background px-4 text-sm font-medium transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none sm:hidden"
            type="button"
            @click="mobileFiltersOpen = !mobileFiltersOpen"
        >
            <span class="flex min-w-0 items-center gap-2">
                <SlidersHorizontal class="size-4 shrink-0" />
                <span>
                    {{ searchable ? 'Search & filters' : 'Filters' }}
                </span>
            </span>

            <ChevronDown
                class="size-4 shrink-0 transition-transform duration-200"
                :class="{ 'rotate-180': mobileFiltersOpen }"
            />
        </button>

        <!-- Filter Panel -->
        <div
            :id="panelId"
            :class="[
                'min-w-0',
                hasCollapsibleContent
                    ? mobileFiltersOpen
                        ? 'mt-3'
                        : 'hidden'
                    : '',
                'sm:mt-0 sm:block',
            ]"
        >
            <!-- Search + Filters -->
            <div
                class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
            >
                <div v-if="searchable" class="min-w-0">
                    <AppSearch
                        v-model="searchValue"
                        :disabled="searchDisabled"
                        :placeholder="searchPlaceholder"
                        @search="emit('search')"
                    />
                </div>

                <slot name="filters" />
            </div>

            <!-- Actions -->
            <div
                class="mt-3 flex min-w-0 flex-col gap-2 sm:flex-row sm:justify-end"
            >
                <Button
                    :disabled="searchDisabled"
                    class="h-9 w-full cursor-pointer sm:w-auto"
                    size="sm"
                    type="button"
                    @click="emit('search')"
                >
                    <span>{{ searchButtonLabel }}</span>
                    <kbd
                        class="ml-2 hidden rounded border border-current/20 px-1.5 py-0.5 text-[10px] font-normal opacity-70 sm:inline-block"
                    >
                        {{ ACTION_SHORTCUTS.search.label }}
                    </kbd>
                </Button>

                <Button
                    :disabled="isClearDisabled"
                    class="h-9 w-full cursor-pointer sm:w-auto"
                    size="sm"
                    type="button"
                    variant="outline"
                    @click="emit('clear')"
                >
                    <span>{{ clearButtonLabel }}</span>
                    <kbd
                        class="ml-2 hidden rounded border border-current/20 px-1.5 py-0.5 text-[10px] font-normal opacity-70 sm:inline-block"
                    >
                        {{ ACTION_SHORTCUTS.clearSearch.label }}
                    </kbd>
                </Button>
            </div>

        </div>
    </div>
</template>
