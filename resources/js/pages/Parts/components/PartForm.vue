<script lang="ts" setup>
import type { InertiaForm } from '@inertiajs/vue3';
import {
    Box,
    Layers3,
    Ruler,
    Scale,
    Settings2,
    ToggleRight,
} from '@lucide/vue';

import AppImagePicker from '@/components/common/AppImagePicker.vue';
import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import { partPage } from '@/config/parts';
import { partSchema } from '@/schemas/partSchema';
import type { PartFormData } from '@/types/part';
import { useZodFormValidation } from '@/composables/useZodFormValidation';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<PartFormData>;
        errors?: Partial<Record<keyof PartFormData, string>>;
        processing?: boolean;
        submitLabel?: string;
        currentImage?: string | null;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: partPage.form.createSubmitLabel,
        currentImage: null,
    },
);

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

// Frontend validation
const { errors: frontendErrors, validate } = useZodFormValidation(
    partSchema,
    () => props.form,
);

const handleSubmit = () => {
    if (validate()) {
        emit('submit');
    }
};

// Image
const handleImageChange = (): void => {
    props.form.remove_image = false;
};

const handleImageRemove = (): void => {
    props.form.remove_image = true;
};
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <!-- Part Information -->
        <FormSection
            :description="partPage.form.sectionDescription"
            :title="partPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Section -->
                <AppField
                    :label="partPage.form.sectionLabel"
                    input-id="part-section"
                    required
                >
                    <AppSectionSelect
                        id="part-section"
                        v-model="props.form.section_id"
                        :disabled="props.processing"
                        :error="
                            frontendErrors.section_id ?? props.errors.section_id
                        "
                        :icon="Layers3"
                        :placeholder="partPage.form.sectionPlaceholder"
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Part Name -->
                <AppField
                    :label="partPage.form.nameLabel"
                    input-id="part-name"
                    required
                >
                    <AppInput
                        id="part-name"
                        v-model="props.form.name"
                        :disabled="props.processing"
                        :error="frontendErrors.name ?? props.errors.name"
                        :icon="Box"
                        :placeholder="partPage.form.namePlaceholder"
                    />
                </AppField>

                <!-- New Part Name -->
                <AppField
                    :label="partPage.form.newPartNameLabel"
                    input-id="part-new-part-name"
                >
                    <AppInput
                        id="part-new-part-name"
                        v-model="props.form.new_part_name"
                        :disabled="props.processing"
                        :error="
                            frontendErrors.new_part_name ??
                            props.errors.new_part_name
                        "
                        :icon="Settings2"
                        :placeholder="partPage.form.newPartNamePlaceholder"
                    />
                </AppField>

                <!-- Cutting Size -->
                <AppField
                    :label="partPage.form.cuttingSizeLabel"
                    input-id="part-cutting-size"
                >
                    <AppInput
                        id="part-cutting-size"
                        v-model="props.form.cutting_size"
                        :disabled="props.processing"
                        :error="
                            frontendErrors.cutting_size ??
                            props.errors.cutting_size
                        "
                        :icon="Ruler"
                        :placeholder="partPage.form.cuttingSizePlaceholder"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Material Details -->
        <FormSection
            description="Configure the part weight and raw material requirements."
            title="Material Details"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Part Weight -->
                <AppField
                    :label="partPage.form.partWeightLabel"
                    input-id="part-part-weight"
                >
                    <AppInput
                        id="part-part-weight"
                        v-model="props.form.part_weight"
                        :disabled="props.processing"
                        :error="
                            frontendErrors.part_weight ??
                            props.errors.part_weight
                        "
                        :icon="Scale"
                        :placeholder="partPage.form.partWeightPlaceholder"
                    />
                </AppField>

                <!-- Raw Material Weight -->
                <AppField
                    :label="partPage.form.rawMaterialLabel"
                    input-id="part-raw-material-weight"
                >
                    <AppInput
                        id="part-raw-material-weight"
                        v-model="props.form.raw_material_weight"
                        :disabled="props.processing"
                        :error="
                            frontendErrors.raw_material_weight ??
                            props.errors.raw_material_weight
                        "
                        :icon="Scale"
                        :placeholder="partPage.form.rawMaterialPlaceholder"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Image & Status -->
        <FormSection
            description="Manage the part image and production availability."
            title="Part Image & Status"
        >
            <div class="grid min-w-0 gap-5">
                <!-- Image -->
                <AppField
                    :label="partPage.form.imageLabel"
                    input-id="part-image"
                >
                    <AppImagePicker
                        v-model="props.form.image"
                        :current-image="props.currentImage"
                        :disabled="props.processing"
                        :error="frontendErrors.image ?? props.errors.image"
                        :placeholder="partPage.form.imagePlaceholder"
                        @change="handleImageChange"
                        @remove="handleImageRemove"
                    />
                </AppField>

                <!-- Status -->
                <ToggleCardInput
                    v-model="props.form.status"
                    :description="
                        props.form.status
                            ? 'Part is active and available for production.'
                            : 'Part is inactive and unavailable for production.'
                    "
                    :disabled="props.processing"
                    :icon="ToggleRight"
                    :label="partPage.form.statusLabel"
                    title="Part Status"
                />
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :cancel-label="partPage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
