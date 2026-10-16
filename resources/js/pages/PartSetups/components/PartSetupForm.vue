<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Clock3, Image, Settings2, Wrench } from '@lucide/vue';

import AppImagePicker from '@/components/common/AppImagePicker.vue';
import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import { partSetupPage } from '@/config/partSetups';
import type { PartSetupFormData } from '@/types/partSetup';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<PartSetupFormData>;
        errors?: Partial<Record<keyof PartSetupFormData, string>>;
        processing?: boolean;
        submitLabel?: string;
        currentImage?: string | null;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: partSetupPage.form.createSubmitLabel,
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
        <!-- Part & Setup -->
        <FormSection
            :title="partSetupPage.form.sectionTitle"
            :description="partSetupPage.form.sectionDescription"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Part -->
                <AppField
                    :label="partSetupPage.form.partLabel"
                    input-id="part-setup-part"
                    :error="props.errors.part_id"
                    required
                >
                    <AppPartSelect
                        id="part-setup-part"
                        v-model="props.form.part_id"
                        :placeholder="partSetupPage.form.partPlaceholder"
                        :disabled="props.processing"
                        :icon="Wrench"
                        :error="props.errors.part_id"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Setup -->
                <AppField
                    :label="partSetupPage.form.setupLabel"
                    input-id="part-setup-setup"
                    :error="props.errors.setup_id"
                    required
                >
                    <AppSetupSelect
                        id="part-setup-setup"
                        v-model="props.form.setup_id"
                        :placeholder="partSetupPage.form.setupPlaceholder"
                        :disabled="props.processing"
                        :icon="Settings2"
                        :error="props.errors.setup_id"
                        searchable
                        clearable
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Cycle Time -->
        <FormSection
            title="Cycle Time"
            description="Configure the machine and actual cycle time for this part setup."
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Machine Cycle Time -->
                <AppField
                    :label="partSetupPage.form.machineCycleTimeLabel"
                    input-id="part-setup-machine-cycle-time"
                    :error="props.errors.machine_cycle_time"
                    required
                >
                    <AppInput
                        id="part-setup-machine-cycle-time"
                        v-model="props.form.machine_cycle_time"
                        type="number"
                        min="0"
                        step="1"
                        :placeholder="
                            partSetupPage.form.machineCycleTimePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Clock3"
                        :error="props.errors.machine_cycle_time"
                        required
                    />

                    <p class="mt-2 text-xs leading-5 text-muted-foreground">
                        {{ partSetupPage.form.machineCycleTimeHelp }}
                    </p>
                </AppField>

                <!-- Actual Cycle Time -->
                <AppField
                    :label="partSetupPage.form.actualCycleTimeLabel"
                    input-id="part-setup-actual-cycle-time"
                    :error="props.errors.actual_cycle_time"
                    required
                >
                    <AppInput
                        id="part-setup-actual-cycle-time"
                        v-model="props.form.actual_cycle_time"
                        type="number"
                        min="0"
                        step="1"
                        :placeholder="
                            partSetupPage.form.actualCycleTimePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Clock3"
                        :error="props.errors.actual_cycle_time"
                        required
                    />

                    <p class="mt-2 text-xs leading-5 text-muted-foreground">
                        {{ partSetupPage.form.actualCycleTimeHelp }}
                    </p>
                </AppField>
            </div>
        </FormSection>

        <!-- Image -->
        <FormSection
            title="Setup Image"
            description="Upload an optional image for this part setup."
        >
            <AppField
                :label="partSetupPage.form.imageLabel"
                input-id="part-setup-image"
                :error="props.errors.image"
            >
                <AppImagePicker
                    v-model="props.form.image"
                    :current-image="props.currentImage"
                    :disabled="props.processing"
                    :error="props.errors.image"
                    :placeholder="partSetupPage.form.imagePlaceholder"
                    @change="handleImageChange"
                    @remove="handleImageRemove"
                />
            </AppField>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :submit-label="submitLabel"
            :cancel-label="partSetupPage.form.cancelLabel"
            :processing="processing"
            @cancel="emit('cancel')"
        />
    </form>
</template>
