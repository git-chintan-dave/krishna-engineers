<script lang="ts" setup>
import {computed, ref, useId, useSlots} from 'vue';
import {RotateCcw, Search, SlidersHorizontal, X} from '@lucide/vue';

import AppSearch from '@/components/common/AppSearch.vue';
import {Button} from '@/components/ui/button';
import {Popover, PopoverContent, PopoverTrigger,} from '@/components/ui/popover';

import {ACTION_SHORTCUTS, useActionShortcuts,} from '@/composables/useActionShortcuts';

const props = withDefaults(
    defineProps<{
        search?: string;
        searchable?: boolean;
        searchPlaceholder?: string;
        searchButtonLabel?: string;
        clearButtonLabel?: string;
        searchDisabled?: boolean;
        clearDisabled?: boolean;
        activeFilterCount?: number;
    }>(),
    {
        search: '',
        searchable: true,
        searchPlaceholder: 'Search...',
        searchButtonLabel: 'Search',
        clearButtonLabel: 'Clear',
        searchDisabled: false,
        clearDisabled: undefined,
        activeFilterCount: 0,
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

const hasFilterPanel = computed(() => Boolean(slots.filters));

const filtersOpen = ref(false);

const closeFilters = (): void => {
    filtersOpen.value = false;
};

const handleSearch = (): void => {
    emit('search');
    closeFilters();
};

const handleClear = (): void => {
    emit('clear');
    closeFilters();
};

useActionShortcuts(
    computed(() => ({
        search: {
            handler: handleSearch,
            enabled: computed(
                () => props.searchable && !props.searchDisabled,
            ),
        },
        clearSearch: {
            handler: handleClear,
            enabled: computed(() => !isClearDisabled.value),
        },
    })),
);
</script>

<template>
    <div class="min-w-0" role="search">
        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
            <div v-if="searchable" class="min-w-0 flex-1">
                <AppSearch
                    v-model="searchValue"
                    :disabled="searchDisabled"
                    :placeholder="searchPlaceholder"
                    @search="handleSearch"
                />
            </div>

            <div class="grid min-w-0 grid-cols-2 gap-2 sm:flex sm:shrink-0">
                <Popover
                    v-if="hasFilterPanel"
                    v-model:open="filtersOpen"
                >
                    <PopoverTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="h-10 w-full min-w-0 gap-2 px-3 sm:w-auto"
                            :disabled="searchDisabled"
                            :aria-expanded="filtersOpen"
                            :aria-controls="panelId"
                        >
                            <SlidersHorizontal class="size-4 shrink-0" />

                            <span>Filters</span>

                            <span
                                v-if="activeFilterCount >= 0"
                                class="inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-primary-foreground"
                                :aria-label="`${activeFilterCount} active filters`"
                            >
                                {{ activeFilterCount }}
                            </span>
                        </Button>
                    </PopoverTrigger>

                    <PopoverContent
                        align="end"
                        side="bottom"
                        :side-offset="8"
                        class="flex max-h-[min(80dvh,36rem)] w-[calc(100vw-2rem)] max-w-[26rem] flex-col gap-0 overflow-hidden p-0 sm:w-[26rem]"
                    >
                        <div
                            class="flex shrink-0 items-start justify-between gap-3 border-b px-4 py-3"
                        >
                            <div class="min-w-0 space-y-1">
                                <h3 class="text-sm font-semibold">
                                    Filter results
                                </h3>

                                <p class="text-xs text-muted-foreground">
                                    Narrow down the table data.
                                </p>
                            </div>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-8 shrink-0"
                                aria-label="Close filters"
                                @click="closeFilters"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>

                        <div
                            :id="panelId"
                            class="min-h-0 flex-1 space-y-4 overflow-x-hidden overflow-y-auto overscroll-contain p-4"
                        >
                            <slot name="filters" />
                        </div>

                        <div
                            class="flex shrink-0 gap-2 border-t bg-background p-3"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                class="h-10 min-w-0 flex-1 gap-2 px-3"
                                :disabled="isClearDisabled"
                                @click="handleClear"
                            >
                                <RotateCcw class="size-4 shrink-0" />

                                <span class="truncate">
                                    {{ clearButtonLabel }}
                                </span>
                            </Button>

                            <Button
                                type="button"
                                class="h-10 min-w-0 flex-1 gap-2 px-3"
                                :disabled="searchDisabled"
                                @click="handleSearch"
                            >
                                <Search class="size-4 shrink-0" />

                                <span class="truncate">
                                    Apply filters
                                </span>
                            </Button>
                        </div>
                    </PopoverContent>
                </Popover>

                <Button
                    v-if="searchable"
                    type="button"
                    class="h-10 w-full min-w-0 gap-2 px-3 sm:w-auto sm:flex-none"
                    :disabled="searchDisabled"
                    @click="handleSearch"
                >
                    <Search class="size-4 shrink-0" />

                    <span class="truncate">
                        {{ searchButtonLabel }}
                    </span>

                    <kbd
                        class="hidden shrink-0 rounded border border-current/20 px-1.5 py-0.5 text-[10px] font-normal opacity-70 lg:inline-block"
                    >
                        {{ ACTION_SHORTCUTS.search.label }}
                    </kbd>
                </Button>

                <Button
                    v-if="!hasFilterPanel && !searchable"
                    type="button"
                    variant="outline"
                    class="col-span-2 h-10 w-full sm:col-span-1 sm:w-auto"
                    :disabled="isClearDisabled"
                    @click="handleClear"
                >
                    {{ clearButtonLabel }}
                </Button>
            </div>
        </div>
    </div>
</template>
