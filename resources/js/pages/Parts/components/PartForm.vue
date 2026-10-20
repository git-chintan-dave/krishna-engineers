
<script lang="ts" setup>
import type {InertiaForm} from '@inertiajs/vue3';
import {ref, watch} from 'vue';
import {Box, Layers3, Ruler, Scale, Settings2, ToggleRight} from '@lucide/vue';

import AppImagePicker from '@/components/common/AppImagePicker.vue';
import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import {partPage} from '@/config/parts';
import {partSchema} from '@/schemas/partSchema';
import type {PartFormData} from '@/types/part';

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

// Validation
const frontendErrors = ref<Partial<Record<keyof PartFormData, string>>>({});
const hasSubmitted = ref(false);

const validateForm = (): boolean => {
    const result = partSchema.safeParse(props.form);

    frontendErrors.value = {};

    if (result.success) {
        return true;
    }

    for (const issue of result.error.issues) {
        const field = issue.path[0] as keyof PartFormData;

        if (frontendErrors.value[field] === undefined) {
            frontendErrors.value[field] = issue.message;
        }
    }

    return false;
};

const getError = (field: keyof PartFormData): string | undefined =>
    frontendErrors.value[field] ?? props.errors[field];

const handleSubmit = (): void => {
    hasSubmitted.value = true;

    if (validateForm()) {
        emit('submit');
    }
};

// Revalidate after the user changes values following a failed submission.
watch(
    () => [
        props.form.name,
        props.form.new_part_name,
        props.form.section_id,
        props.form.cutting_size,
        props.form.part_weight,
        props.form.raw_material_weight,
        props.form.image,
        props.form.remove_image,
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
                <!-- Part Name -->
                <AppField
                    :error="getError('name')"
                    :label="partPage.form.nameLabel"
                    input-id="part-name"
                    required
                >
                    <AppInput
                        id="part-name"
                        v-model="props.form.name"
                        :disabled="props.processing"
                        :error="getError('name')"
                        :icon="Box"
                        :placeholder="partPage.form.namePlaceholder"
                    />
                </AppField>

                <!-- New Part Name -->
                <AppField
                    :error="getError('new_part_name')"
                    :label="partPage.form.newPartNameLabel"
                    input-id="part-new-part-name"
                >
                    <AppInput
                        id="part-new-part-name"
                        v-model="props.form.new_part_name"
                        :disabled="props.processing"
                        :error="getError('new_part_name')"
                        :icon="Settings2"
                        :placeholder="partPage.form.newPartNamePlaceholder"
                    />
                </AppField>

                <!-- Section -->
                <AppField
                    :error="getError('section_id')"
                    :label="partPage.form.sectionLabel"
                    input-id="part-section"
                    required
                >
                    <AppSectionSelect
                        id="part-section"
                        v-model="props.form.section_id"
                        :disabled="props.processing"
                        :error="getError('section_id')"
                        :icon="Layers3"
                        :placeholder="partPage.form.sectionPlaceholder"
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Cutting Size -->
                <AppField
                    :error="getError('cutting_size')"
                    :label="partPage.form.cuttingSizeLabel"
                    input-id="part-cutting-size"
                >
                    <AppInput
                        id="part-cutting-size"
                        v-model="props.form.cutting_size"
                        :disabled="props.processing"
                        :error="getError('cutting_size')"
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
                    :error="getError('part_weight')"
                    :label="partPage.form.partWeightLabel"
                    input-id="part-part-weight"
                >
                    <AppInput
                        id="part-part-weight"
                        v-model="props.form.part_weight"
                        :disabled="props.processing"
                        :error="getError('part_weight')"
                        :icon="Scale"
                        :placeholder="partPage.form.partWeightPlaceholder"
                    />
                </AppField>

                <!-- Raw Material Weight -->
                <AppField
                    :error="getError('raw_material_weight')"
                    :label="partPage.form.rawMaterialLabel"
                    input-id="part-raw-material-weight"
                >
                    <AppInput
                        id="part-raw-material-weight"
                        v-model="props.form.raw_material_weight"
                        :disabled="props.processing"
                        :error="getError('raw_material_weight')"
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
                    :error="getError('image')"
                    :label="partPage.form.imageLabel"
                    input-id="part-image"
                >
                    <AppImagePicker
                        v-model="props.form.image"
                        :current-image="props.currentImage"
                        :disabled="props.processing"
                        :error="getError('image')"
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
                    :error="getError('status')"
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
