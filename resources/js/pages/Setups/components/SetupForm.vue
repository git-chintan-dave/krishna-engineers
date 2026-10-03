<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    Settings2,
    ToggleRight,
} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import { setupPage } from '@/config/setups';
import type { SetupFormData } from '@/types/setup';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<SetupFormData>;
        errors?: Partial<
            Record<keyof SetupFormData, string>
        >;
        processing?: boolean;
        submitLabel?: string;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Setup',
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
        <!-- Setup Information -->
        <FormSection
            :title="setupPage.form.sectionTitle"
            :description="
                setupPage.form.sectionDescription
            "
        >
            <div class="grid min-w-0 gap-5">
                <!-- Setup Name -->
                <AppField
                    :label="setupPage.form.nameLabel"
                    input-id="setup-name"
                    :error="props.errors.name"
                    required
                >
                    <AppInput
                        id="setup-name"
                        v-model="props.form.name"
                        :placeholder="
                            setupPage.form.namePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Settings2"
                        :error="props.errors.name"
                        required
                    />
                </AppField>

                <!-- Status -->
                <ToggleCardInput
                    v-model="props.form.status"
                    title="Setup Status"
                    label="Status"
                    active-text="Setup is currently active."
                    inactive-text="Setup is currently inactive."
                    :disabled="props.processing"
                    :error="props.errors.status"
                    :icon="ToggleRight"
                />
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :submit-label="submitLabel"
            :processing="processing"
            :cancel-label="setupPage.form.cancelLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
