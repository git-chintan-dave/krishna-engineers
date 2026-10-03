<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    Layers3,
    ToggleRight,
} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import { sectionPage } from '@/config/sections';
import type { SectionFormData } from '@/types/section';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<SectionFormData>;
        errors?: Partial<
            Record<keyof SectionFormData, string>
        >;
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
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        @submit.prevent="emit('submit')"
    >
        <!-- Section Information -->
        <FormSection
            :title="sectionPage.form.sectionTitle"
            :description="
                sectionPage.form.sectionDescription
            "
        >
            <div class="grid min-w-0 gap-5">
                <!-- Section Name -->
                <AppField
                    :label="sectionPage.form.nameLabel"
                    input-id="section-name"
                    :error="props.errors.name"
                    required
                >
                    <AppInput
                        id="section-name"
                        v-model="props.form.name"
                        :placeholder="
                            sectionPage.form.namePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Layers3"
                        :error="props.errors.name"
                        autocomplete="off"
                        required
                    />
                </AppField>

                <!-- Status -->
                <ToggleCardInput
                    v-model="props.form.status"
                    title="Section Status"
                    label="Status"
                    active-text="Section is currently active."
                    inactive-text="Section is currently inactive."
                    :disabled="props.processing"
                    :error="props.errors.status"
                    :icon="ToggleRight"
                />
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :processing="processing"
            :submit-label="submitLabel"
            :cancel-label="sectionPage.form.cancelLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
