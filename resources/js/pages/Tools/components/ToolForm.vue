<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    Image,
    Settings2,
    ToggleRight,
    Wrench,
} from '@lucide/vue';

import AppImagePicker from '@/components/common/AppImagePicker.vue';
import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import AppToolTypeSelect from '@/components/selects/AppToolTypeSelect.vue';
import { toolPage } from '@/config/tools';
import type { ToolFormData } from '@/types/tool';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<ToolFormData>;
        errors?: Partial<
            Record<keyof ToolFormData, string>
        >;
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
    <form
        class="min-w-0 space-y-6"
        @submit.prevent="emit('submit')"
    >
        <!-- Tool Information -->
        <FormSection
            :title="toolPage.form.toolTitle"
            :description="toolPage.form.toolDescription"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Type -->
                <AppField
                    :label="toolPage.form.typeLabel"
                    input-id="tool-type"
                    :error="props.errors.type"
                    required
                >
                    <AppToolTypeSelect
                        id="tool-type"
                        v-model="props.form.type"
                        :placeholder="
                            toolPage.form.typePlaceholder
                        "
                        :error="props.errors.type"
                        :disabled="props.processing"
                        :icon="Wrench"
                        searchable
                        required
                    />
                </AppField>

                <!-- Name -->
                <AppField
                    :label="toolPage.form.nameLabel"
                    input-id="tool-name"
                    :error="props.errors.name"
                    required
                >
                    <AppInput
                        id="tool-name"
                        v-model="props.form.name"
                        :placeholder="
                            toolPage.form.namePlaceholder
                        "
                        :error="props.errors.name"
                        :disabled="props.processing"
                        :icon="Settings2"
                        autocomplete="off"
                        required
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Image & Status -->
        <FormSection
            :title="toolPage.form.detailsTitle ?? 'Tool Details'"
            :description="
                toolPage.form.detailsDescription ??
                'Manage the tool image and status.'
            "
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Image -->
                <AppField
                    :label="toolPage.form.imageLabel"
                    input-id="tool-image"
                    class="md:col-span-2"
                >
                    <AppImagePicker
                        v-model="props.form.image"
                        :current-image="props.currentImage"
                        :disabled="props.processing"
                        :error="props.errors.image"
                        :placeholder="
                            toolPage.form.imagePlaceholder
                        "
                        :icon="Image"
                        @change="handleImageChange"
                        @remove="handleImageRemove"
                    />
                </AppField>

                <!-- Status -->
                <AppField
                    :label="toolPage.form.statusLabel"
                    input-id="tool-status"
                    class="md:col-span-2"
                >
                    <ToggleCardInput
                        v-model="props.form.status"
                        title="Tool Status"
                        label="Status"
                        active-text="Tool is currently active."
                        inactive-text="
                            Tool is currently inactive.
                        "
                        :disabled="props.processing"
                        :error="props.errors.status"
                        :icon="ToggleRight"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :processing="props.processing"
            :submit-label="props.submitLabel"
            :cancel-label="toolPage.form.cancelLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
