
<script lang="ts" setup>
import {ref, watch} from 'vue';
import type {InertiaForm} from '@inertiajs/vue3';
import {Layers3, Ruler, Scale} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import {sectionSizePage} from '@/config/sectionSizes';
import {sectionSizeSchema} from '@/schemas/sectionSizeSchema';
import type {SectionSizeFormData} from '@/types/sectionSize';

// Props
const props = defineProps<{
    form: InertiaForm<SectionSizeFormData>;
    errors: Partial<Record<keyof SectionSizeFormData, string>>;
    processing: boolean;
    submitLabel: string;
}>();

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

// Validation
const frontendErrors = ref<
    Partial<Record<keyof SectionSizeFormData, string>>
>({});
const hasSubmitted = ref(false);

const validateForm = (): boolean => {
    const result = sectionSizeSchema.safeParse(props.form);
    const errors: Partial<Record<keyof SectionSizeFormData, string>> = {};

    if (!result.success) {
        for (const issue of result.error.issues) {
            const field = issue.path[0] as keyof SectionSizeFormData;

            if (field && !errors[field]) {
                errors[field] = issue.message;
            }
        }
    }

    frontendErrors.value = errors;

    return result.success;
};

const getError = (
    field: keyof SectionSizeFormData,
): string | undefined => frontendErrors.value[field] ?? props.errors[field];

const handleSubmit = (): void => {
    hasSubmitted.value = true;

    if (!validateForm()) {
        return;
    }

    emit('submit');
};

watch(
    () => [
        props.form.section_id,
        props.form.size,
        props.form.weight,
    ],
    () => {
        if (hasSubmitted.value) {
            validateForm();
        }
    },
);
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <!-- Section Size Information -->
        <FormSection
            :description="sectionSizePage.form.sectionDescription"
            :title="sectionSizePage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Section -->
                <AppField
                    :error="getError('section_id')"
                    :label="sectionSizePage.form.sectionLabel"
                    input-id="section-size-section"
                    required
                >
                    <AppSectionSelect
                        id="section-size-section"
                        v-model="props.form.section_id"
                        :disabled="props.processing"
                        :error="getError('section_id')"
                        :icon="Layers3"
                        :placeholder="sectionSizePage.form.sectionPlaceholder"
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Size -->
                <AppField
                    :error="getError('size')"
                    :label="sectionSizePage.form.sizeLabel"
                    input-id="section-size-size"
                    required
                >
                    <AppInput
                        id="section-size-size"
                        v-model="props.form.size"
                        :disabled="props.processing"
                        :error="getError('size')"
                        :icon="Ruler"
                        :placeholder="sectionSizePage.form.sizePlaceholder"
                        autocomplete="off"
                    />
                </AppField>

                <!-- Weight -->
                <AppField
                    :error="getError('weight')"
                    :label="sectionSizePage.form.weightLabel"
                    input-id="section-size-weight"
                    required
                >
                    <AppInput
                        id="section-size-weight"
                        v-model="props.form.weight"
                        :disabled="props.processing"
                        :error="getError('weight')"
                        :icon="Scale"
                        :placeholder="sectionSizePage.form.weightPlaceholder"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :cancel-label="sectionSizePage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
