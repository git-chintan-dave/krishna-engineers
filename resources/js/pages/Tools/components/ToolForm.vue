
<script lang="ts" setup>
import {ref, watch} from 'vue';
import type {InertiaForm} from '@inertiajs/vue3';
import {Image, Settings2, ToggleRight, Wrench} from '@lucide/vue';

import AppImagePicker from '@/components/common/AppImagePicker.vue';
import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import AppToolTypeSelect from '@/components/selects/AppToolTypeSelect.vue';
import {toolPage} from '@/config/tools';
import {toolSchema} from '@/schemas/toolSchema';
import type {ToolFormData} from '@/types/tool';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<ToolFormData>;
        errors?: Partial<Record<keyof ToolFormData, string>>;
        processing?: boolean;
        submitLabel?: string;
        currentImage?: string | null;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Tool',
        currentImage: null,
    },
);

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

// Validation
const frontendErrors = ref<
    Partial<Record<keyof ToolFormData, string>>
>({});
const hasSubmitted = ref(false);

const validateForm = (): boolean => {
    const result = toolSchema.safeParse(props.form);
    const errors: Partial<Record<keyof ToolFormData, string>> = {};

    if (!result.success) {
        for (const issue of result.error.issues) {
            const field = issue.path[0] as keyof ToolFormData;

            if (field && !errors[field]) {
                errors[field] = issue.message;
            }
        }
    }

    frontendErrors.value = errors;

    return result.success;
};

const getError = (field: keyof ToolFormData): string | undefined =>
    frontendErrors.value[field] ?? props.errors[field];

const handleSubmit = (): void => {
    hasSubmitted.value = true;

    if (!validateForm()) {
        return;
    }

    emit('submit');
};

watch(
    () => [
        props.form.type,
        props.form.name,
        props.form.image,
        props.form.status,
    ],
    () => {
        if (hasSubmitted.value) {
            validateForm();
        }
    },
);

// Image
const handleImageChange = (): void => {
    props.form.remove_image = false;

    if (hasSubmitted.value) {
        validateForm();
    }
};

const handleImageRemove = (): void => {
    props.form.remove_image = true;

    if (hasSubmitted.value) {
        validateForm();
    }
};
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <!-- Tool Information -->
        <FormSection
            :description="toolPage.form.toolDescription"
            :title="toolPage.form.toolTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Type -->
                <AppField
                    :error="getError('type')"
                    :label="toolPage.form.typeLabel"
                    input-id="tool-type"
                    required
                >
                    <AppToolTypeSelect
                        id="tool-type"
                        v-model="props.form.type"
                        :disabled="props.processing"
                        :error="getError('type')"
                        :icon="Wrench"
                        :placeholder="toolPage.form.typePlaceholder"
                        searchable
                    />
                </AppField>

                <!-- Name -->
                <AppField
                    :error="getError('name')"
                    :label="toolPage.form.nameLabel"
                    input-id="tool-name"
                    required
                >
                    <AppInput
                        id="tool-name"
                        v-model="props.form.name"
                        :disabled="props.processing"
                        :error="getError('name')"
                        :icon="Settings2"
                        :placeholder="toolPage.form.namePlaceholder"
                        autocomplete="off"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Image & Status -->
        <FormSection
            :description="
                toolPage.form.detailsDescription ??
                'Manage the tool image and status.'
            "
            :title="toolPage.form.detailsTitle ?? 'Tool Details'"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Image -->
                <AppField
                    :error="getError('image')"
                    :label="toolPage.form.imageLabel"
                    class="md:col-span-2"
                    input-id="tool-image"
                >
                    <AppImagePicker
                        v-model="props.form.image"
                        :current-image="props.currentImage"
                        :disabled="props.processing"
                        :error="getError('image')"
                        :icon="Image"
                        :placeholder="toolPage.form.imagePlaceholder"
                        @change="handleImageChange"
                        @remove="handleImageRemove"
                    />
                </AppField>

                <!-- Status -->
                <AppField
                    :error="getError('status')"
                    :label="toolPage.form.statusLabel"
                    class="md:col-span-2"
                    input-id="tool-status"
                >
                    <ToggleCardInput
                        v-model="props.form.status"
                        :disabled="props.processing"
                        :error="getError('status')"
                        :icon="ToggleRight"
                        active-text="Tool is currently active."
                        inactive-text="Tool is currently inactive."
                        label="Status"
                        title="Tool Status"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :cancel-label="toolPage.form.cancelLabel"
            :processing="props.processing"
            :submit-label="props.submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
