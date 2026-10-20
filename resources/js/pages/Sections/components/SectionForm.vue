
<script lang="ts" setup>
import {ref, watch} from 'vue';
import type {InertiaForm} from '@inertiajs/vue3';
import {Layers3, ToggleRight} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import {sectionPage} from '@/config/sections';
import {sectionSchema} from '@/schemas/sectionSchema';
import type {SectionFormData} from '@/types/section';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<SectionFormData>;
        errors?: Partial<Record<keyof SectionFormData, string>>;
        processing?: boolean;
        submitLabel?: string;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Section',
    },
);

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

// Validation
const frontendErrors = ref<
    Partial<Record<keyof SectionFormData, string>>
>({});
const hasSubmitted = ref(false);

const validateForm = (): boolean => {
    const result = sectionSchema.safeParse(props.form);
    const errors: Partial<Record<keyof SectionFormData, string>> = {};

    if (!result.success) {
        for (const issue of result.error.issues) {
            const field = issue.path[0] as keyof SectionFormData;

            if (field && !errors[field]) {
                errors[field] = issue.message;
            }
        }
    }

    frontendErrors.value = errors;

    return result.success;
};

const getError = (field: keyof SectionFormData): string | undefined =>
    frontendErrors.value[field] ?? props.errors[field];

const handleSubmit = (): void => {
    hasSubmitted.value = true;

    if (!validateForm()) {
        return;
    }

    emit('submit');
};

watch(
    () => [props.form.name, props.form.status],
    () => {
        if (hasSubmitted.value) {
            validateForm();
        }
    },
);
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <!-- Section Information -->
        <FormSection
            :description="sectionPage.form.sectionDescription"
            :title="sectionPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5">
                <!-- Section Name -->
                <AppField
                    :error="getError('name')"
                    :label="sectionPage.form.nameLabel"
                    input-id="section-name"
                    required
                >
                    <AppInput
                        id="section-name"
                        v-model="props.form.name"
                        :disabled="props.processing"
                        :error="getError('name')"
                        :icon="Layers3"
                        :placeholder="sectionPage.form.namePlaceholder"
                        autocomplete="off"
                    />
                </AppField>

                <!-- Status -->
                <ToggleCardInput
                    v-model="props.form.status"
                    :disabled="props.processing"
                    :error="getError('status')"
                    :icon="ToggleRight"
                    active-text="Section is currently active."
                    inactive-text="Section is currently inactive."
                    label="Status"
                    title="Section Status"
                />
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :cancel-label="sectionPage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
