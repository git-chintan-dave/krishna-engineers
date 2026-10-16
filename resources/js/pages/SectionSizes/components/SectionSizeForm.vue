<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Layers3, Ruler, Scale } from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import { sectionSizePage } from '@/config/sectionSizes';
import type { SectionSizeFormData } from '@/types/sectionSize';

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
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="emit('submit')">
        <!-- Section Size Information -->
        <FormSection
            :title="sectionSizePage.form.sectionTitle"
            :description="sectionSizePage.form.sectionDescription"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Section -->
                <AppField
                    :label="sectionSizePage.form.sectionLabel"
                    input-id="section-size-section"
                    :error="props.errors.section_id"
                    required
                >
                    <AppSectionSelect
                        id="section-size-section"
                        v-model="props.form.section_id"
                        :placeholder="sectionSizePage.form.sectionPlaceholder"
                        :disabled="props.processing"
                        :icon="Layers3"
                        :error="props.errors.section_id"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Size -->
                <AppField
                    :label="sectionSizePage.form.sizeLabel"
                    input-id="section-size-size"
                    :error="props.errors.size"
                    required
                >
                    <AppInput
                        id="section-size-size"
                        v-model="props.form.size"
                        :placeholder="sectionSizePage.form.sizePlaceholder"
                        :disabled="props.processing"
                        :icon="Ruler"
                        :error="props.errors.size"
                        autocomplete="off"
                        required
                    />
                </AppField>

                <!-- Weight -->
                <AppField
                    :label="sectionSizePage.form.weightLabel"
                    input-id="section-size-weight"
                    :error="props.errors.weight"
                    required
                >
                    <AppInput
                        id="section-size-weight"
                        v-model="props.form.weight"
                        :placeholder="sectionSizePage.form.weightPlaceholder"
                        :disabled="props.processing"
                        :icon="Scale"
                        :error="props.errors.weight"
                        required
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :processing="processing"
            :submit-label="submitLabel"
            :cancel-label="sectionSizePage.form.cancelLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
