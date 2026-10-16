<script lang="ts" setup>
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

// Image
const handleImageChange = (): void => {
    props.form.remove_image = false;
};

const handleImageRemove = (): void => {
    props.form.remove_image = true;
};
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="emit('submit')">
        <!-- Tool Information -->
        <FormSection
            :description="toolPage.form.toolDescription"
            :title="toolPage.form.toolTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Type -->
                <AppField
                    :error="props.errors.type"
                    :label="toolPage.form.typeLabel"
                    input-id="tool-type"
                    required
                >
                    <AppToolTypeSelect
                        id="tool-type"
                        v-model="props.form.type"
                        :disabled="props.processing"
                        :error="props.errors.type"
                        :icon="Wrench"
                        :placeholder="toolPage.form.typePlaceholder"
                        required
                        searchable
                    />
                </AppField>

                <!-- Name -->
                <AppField
                    :error="props.errors.name"
                    :label="toolPage.form.nameLabel"
                    input-id="tool-name"
                    required
                >
                    <AppInput
                        id="tool-name"
                        v-model="props.form.name"
                        :disabled="props.processing"
                        :error="props.errors.name"
                        :icon="Settings2"
                        :placeholder="toolPage.form.namePlaceholder"
                        autocomplete="off"
                        required
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
                    :label="toolPage.form.imageLabel"
                    class="md:col-span-2"
                    input-id="tool-image"
                >
                    <AppImagePicker
                        v-model="props.form.image"
                        :current-image="props.currentImage"
                        :disabled="props.processing"
                        :error="props.errors.image"
                        :icon="Image"
                        :placeholder="toolPage.form.imagePlaceholder"
                        @change="handleImageChange"
                        @remove="handleImageRemove"
                    />
                </AppField>

                <!-- Status -->
                <AppField
                    :label="toolPage.form.statusLabel"
                    class="md:col-span-2"
                    input-id="tool-status"
                >
                    <ToggleCardInput
                        v-model="props.form.status"
                        :disabled="props.processing"
                        :error="props.errors.status"
                        :icon="ToggleRight"
                        active-text="Tool is currently active."
                        inactive-text="
                            Tool is currently inactive.
                        "
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
