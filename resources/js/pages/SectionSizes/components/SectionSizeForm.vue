<script lang="ts" setup>
import type {InertiaForm} from '@inertiajs/vue3';
import {Layers3, Ruler, Scale} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import {sectionSizePage} from '@/config/sectionSizes';
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
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="emit('submit')">
        <!-- Section Size Information -->
        <FormSection
            :description="sectionSizePage.form.sectionDescription"
            :title="sectionSizePage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Section -->
                <AppField
                    :error="props.errors.section_id"
                    :label="sectionSizePage.form.sectionLabel"
                    input-id="section-size-section"
                    required
                >
                    <AppSectionSelect
                        id="section-size-section"
                        v-model="props.form.section_id"
                        :disabled="props.processing"
                        :error="props.errors.section_id"
                        :icon="Layers3"
                        :placeholder="sectionSizePage.form.sectionPlaceholder"
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Size -->
                <AppField
                    :error="props.errors.size"
                    :label="sectionSizePage.form.sizeLabel"
                    input-id="section-size-size"
                    required
                >
                    <AppInput
                        id="section-size-size"
                        v-model="props.form.size"
                        :disabled="props.processing"
                        :error="props.errors.size"
                        :icon="Ruler"
                        :placeholder="sectionSizePage.form.sizePlaceholder"
                        autocomplete="off"
                        required
                    />
                </AppField>

                <!-- Weight -->
                <AppField
                    :error="props.errors.weight"
                    :label="sectionSizePage.form.weightLabel"
                    input-id="section-size-weight"
                    required
                >
                    <AppInput
                        id="section-size-weight"
                        v-model="props.form.weight"
                        :disabled="props.processing"
                        :error="props.errors.weight"
                        :icon="Scale"
                        :placeholder="sectionSizePage.form.weightPlaceholder"
                        required
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
