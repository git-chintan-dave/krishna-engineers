<script setup lang="ts">
import { computed } from 'vue';

import AppSearch from '@/components/common/AppSearch.vue';
import { Button } from '@/components/ui/button';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    'update:search': [value: string];
    search: [];
    clear: [];
}>();

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

const searchValue = computed({
    get: () => props.search,

    set: (value: string) => {
        emit('update:search', value);
    },
});

/*
|--------------------------------------------------------------------------
| Clear State
|--------------------------------------------------------------------------
*/

const isClearDisabled = computed(
    () =>
        props.clearDisabled ??
        !props.search.trim(),
);
</script>

<template>
    <div
        class="flex flex-col gap-3 border-b pb-4 sm:flex-row sm:flex-wrap sm:items-center"
    >
        <!--
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        -->

        <div
            v-if="searchable"
            class="w-full min-w-0 sm:min-w-56 sm:flex-1"
        >
            <AppSearch
                v-model="searchValue"
                :placeholder="searchPlaceholder"
                :disabled="searchDisabled"
                @search="emit('search')"
            />
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Additional Filters
        |--------------------------------------------------------------------------
        -->

        <div
            v-if="$slots.filters"
            class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center"
        >
            <slot name="filters" />
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <div
            class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
        >
            <Button
                type="button"
                size="sm"
                class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
                :disabled="searchDisabled"
                @click="emit('search')"
            >
                {{ searchButtonLabel }}
            </Button>

            <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
                :disabled="isClearDisabled"
                @click="emit('clear')"
            >
                {{ clearButtonLabel }}
            </Button>
        </div>
    </div>
</template>
