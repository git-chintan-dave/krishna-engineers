<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import { useLookup } from '@/composables/useLookup';
import type { SelectOption } from '@/types/common';

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
    }>(),
    {
        modelValue: null,
        sectionId: null,
        placeholder: 'Select section size...',
        searchable: true,
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

const {
    loading,
    sectionSizes,
} = useLookup();

/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/

const loadOptions = async (): Promise<void> => {
    options.value = [];

    if (!props.sectionId) {
        return;
    }

    options.value = await sectionSizes(
        props.sectionId,
    );
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
    <AppSelect
        :model-value="modelValue"
        :options="options"
        :placeholder="placeholder"
        :searchable="searchable"
        :clearable="clearable"
        :disabled="
            disabled ||
            loading ||
            !sectionId
        "
        :loading="loading"
        :error="error"
        @update:model-value="
            emit(
                'update:modelValue',
                $event as number | null,
            )
        "
        @change="
            emit(
                'change',
                $event as number | null,
            )
        "
    />
</template>
