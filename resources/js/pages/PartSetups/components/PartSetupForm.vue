<script lang="ts" setup>
import type {InertiaForm} from '@inertiajs/vue3';
import {Clock3, Settings2, Wrench} from '@lucide/vue';

import AppImagePicker from '@/components/common/AppImagePicker.vue';
import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import {partSetupPage} from '@/config/partSetups';
import type {PartSetupFormData} from '@/types/partSetup';

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
            :description="partSetupPage.form.sectionDescription"
            :title="partSetupPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Part -->
                <AppField
                    :error="props.errors.part_id"
                    :label="partSetupPage.form.partLabel"
                    input-id="part-setup-part"
                    required
                >
                    <AppPartSelect
                        id="part-setup-part"
                        v-model="props.form.part_id"
                        :disabled="props.processing"
                        :error="props.errors.part_id"
                        :icon="Wrench"
                        :placeholder="partSetupPage.form.partPlaceholder"
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Setup -->
                <AppField
                    :error="props.errors.setup_id"
                    :label="partSetupPage.form.setupLabel"
                    input-id="part-setup-setup"
                    required
                >
                    <AppSetupSelect
                        id="part-setup-setup"
                        v-model="props.form.setup_id"
                        :disabled="props.processing"
                        :error="props.errors.setup_id"
                        :icon="Settings2"
                        :placeholder="partSetupPage.form.setupPlaceholder"
                        clearable
                        searchable
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Cycle Time -->
        <FormSection
            description="Configure the machine and actual cycle time for this part setup."
            title="Cycle Time"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Machine Cycle Time -->
                <AppField
                    :error="props.errors.machine_cycle_time"
                    :label="partSetupPage.form.machineCycleTimeLabel"
                    input-id="part-setup-machine-cycle-time"
                    required
                >
                    <AppInput
                        id="part-setup-machine-cycle-time"
                        v-model="props.form.machine_cycle_time"
                        :disabled="props.processing"
                        :error="props.errors.machine_cycle_time"
                        :icon="Clock3"
                        :placeholder="
                            partSetupPage.form.machineCycleTimePlaceholder
                        "
                        min="0"
                        required
                        step="1"
                        type="number"
                    />

                    <p class="mt-2 text-xs leading-5 text-muted-foreground">
                        {{ partSetupPage.form.machineCycleTimeHelp }}
                    </p>
                </AppField>

                <!-- Actual Cycle Time -->
                <AppField
                    :error="props.errors.actual_cycle_time"
                    :label="partSetupPage.form.actualCycleTimeLabel"
                    input-id="part-setup-actual-cycle-time"
                    required
                >
                    <AppInput
                        id="part-setup-actual-cycle-time"
                        v-model="props.form.actual_cycle_time"
                        :disabled="props.processing"
                        :error="props.errors.actual_cycle_time"
                        :icon="Clock3"
                        :placeholder="
                            partSetupPage.form.actualCycleTimePlaceholder
                        "
                        min="0"
                        required
                        step="1"
                        type="number"
                    />

                    <p class="mt-2 text-xs leading-5 text-muted-foreground">
                        {{ partSetupPage.form.actualCycleTimeHelp }}
                    </p>
                </AppField>
            </div>
        </FormSection>

        <!-- Image -->
        <FormSection
            description="Upload an optional image for this part setup."
            title="Setup Image"
        >
            <AppField
                :error="props.errors.image"
                :label="partSetupPage.form.imageLabel"
                input-id="part-setup-image"
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
            :cancel-label="partSetupPage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
