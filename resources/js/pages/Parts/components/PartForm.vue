```vue
<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    Box,
    Image,
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
import type { PartFormData } from '@/types/part';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<PartFormData>;
        errors?: Partial<
            Record<keyof PartFormData, string>
        >;
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
        <!-- Part Information -->
        <FormSection
            :title="partPage.form.sectionTitle"
            :description="
                partPage.form.sectionDescription
            "
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Part Name -->
                <AppField
                    :label="partPage.form.nameLabel"
                    input-id="part-name"
                    :error="props.errors.name"
                    required
                >
                    <AppInput
                        id="part-name"
                        v-model="props.form.name"
                        :placeholder="
                            partPage.form.namePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Box"
                        :error="props.errors.name"
                        required
                    />
                </AppField>

                <!-- New Part Name -->
                <AppField
                    :label="
                        partPage.form.newPartNameLabel
                    "
                    input-id="part-new-part-name"
                    :error="props.errors.new_part_name"
                >
                    <AppInput
                        id="part-new-part-name"
                        v-model="props.form.new_part_name"
                        :placeholder="
                            partPage.form
                                .newPartNamePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Settings2"
                        :error="
                            props.errors.new_part_name
                        "
                    />
                </AppField>

                <!-- Section -->
                <AppField
                    :label="partPage.form.sectionLabel"
                    input-id="part-section"
                    :error="props.errors.section_id"
                >
                    <AppSectionSelect
                        id="part-section"
                        v-model="props.form.section_id"
                        :placeholder="
                            partPage.form.sectionPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Layers3"
                        :error="props.errors.section_id"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Cutting Size -->
                <AppField
                    :label="
                        partPage.form.cuttingSizeLabel
                    "
                    input-id="part-cutting-size"
                    :error="props.errors.cutting_size"
                >
                    <AppInput
                        id="part-cutting-size"
                        v-model="props.form.cutting_size"
                        :placeholder="
                            partPage.form
                                .cuttingSizePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Ruler"
                        :error="props.errors.cutting_size"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Material Details -->
        <FormSection
            title="Material Details"
            description="Configure the part weight and raw material requirements."
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Part Weight -->
                <AppField
                    :label="
                        partPage.form.partWeightLabel
                    "
                    input-id="part-part-weight"
                    :error="props.errors.part_weight"
                >
                    <AppInput
                        id="part-part-weight"
                        v-model="props.form.part_weight"
                        :placeholder="
                            partPage.form
                                .partWeightPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Scale"
                        :error="props.errors.part_weight"
                    />
                </AppField>

                <!-- Raw Material Weight -->
                <AppField
                    :label="
                        partPage.form.rawMaterialLabel
                    "
                    input-id="part-raw-material-weight"
                    :error="
                        props.errors.raw_material_weight
                    "
                >
                    <AppInput
                        id="part-raw-material-weight"
                        v-model="
                            props.form.raw_material_weight
                        "
                        :placeholder="
                            partPage.form
                                .rawMaterialPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Scale"
                        :error="
                            props.errors.raw_material_weight
                        "
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Image & Status -->
        <FormSection
            title="Part Image & Status"
            description="Manage the part image and production availability."
        >
            <div class="grid min-w-0 gap-5">
                <!-- Image -->
                <AppField
                    :label="partPage.form.imageLabel"
                    input-id="part-image"
                    :error="props.errors.image"
                >
                    <AppImagePicker
                        v-model="props.form.image"
                        :current-image="props.currentImage"
                        :disabled="props.processing"
                        :error="props.errors.image"
                        :placeholder="
                            partPage.form.imagePlaceholder
                        "
                        @change="handleImageChange"
                        @remove="handleImageRemove"
                    />
                </AppField>

                <!-- Status -->
                <ToggleCardInput
                    v-model="props.form.status"
                    :label="partPage.form.statusLabel"
                    title="Part Status"
                    :description="
                        props.form.status
                            ? 'Part is active and available for production.'
                            : 'Part is inactive and unavailable for production.'
                    "
                    :disabled="props.processing"
                    :error="props.errors.status"
                    :icon="ToggleRight"
                />
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :submit-label="submitLabel"
            :cancel-label="partPage.form.cancelLabel"
            :processing="processing"
            @cancel="emit('cancel')"
        />
    </form>
</template>
```
