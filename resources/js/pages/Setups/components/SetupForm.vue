<script lang="ts" setup>
import type {InertiaForm} from '@inertiajs/vue3';
import {ref, watch} from 'vue';
import {Settings2, ToggleRight} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import {setupPage} from '@/config/setups';
import {setupSchema} from '@/schemas/setupSchema';
import type {SetupFormData} from '@/types/setup';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<SetupFormData>;
        errors?: Partial<Record<keyof SetupFormData, string>>;
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

// Frontend validation
const frontendErrors = ref<
    Partial<Record<keyof SetupFormData, string>>
>({});

const validate = (): boolean => {
    const result = setupSchema.safeParse({
        name: props.form.name,
        status: props.form.status,
    });

    frontendErrors.value = {};

    if (result.success) {
        return true;
    }

    for (const issue of result.error.issues) {
        const field = issue.path[0] as keyof SetupFormData;

        frontendErrors.value[field] ??= issue.message;
    }

    return false;
};

const handleSubmit = () => {
    if (validate()) {
        emit('submit');
    }
};

watch(
    () => [props.form.name, props.form.status],
    () => {
        if (Object.keys(frontendErrors.value).length > 0) {
            validate();
        }
    },
);
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <!-- Setup Information -->
        <FormSection
            :description="setupPage.form.sectionDescription"
            :title="setupPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5">
                <!-- Setup Name -->
                <AppField
                    :error="frontendErrors.name ?? props.errors.name"
                    :label="setupPage.form.nameLabel"
                    input-id="setup-name"
                    required
                >
                    <AppInput
                        id="setup-name"
                        v-model="props.form.name"
                        :disabled="props.processing"
                        :error="frontendErrors.name ?? props.errors.name"
                        :icon="Settings2"
                        :placeholder="setupPage.form.namePlaceholder"
                    />
                </AppField>

                <!-- Status -->
                <ToggleCardInput
                    v-model="props.form.status"
                    :disabled="props.processing"
                    :error="frontendErrors.status ?? props.errors.status"
                    :icon="ToggleRight"
                    active-text="Setup is currently active."
                    inactive-text="Setup is currently inactive."
                    label="Status"
                    title="Setup Status"
                />
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :cancel-label="setupPage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
