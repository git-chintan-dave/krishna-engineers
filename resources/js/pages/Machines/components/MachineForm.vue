```vue
<script lang="ts" setup>
import type {InertiaForm} from '@inertiajs/vue3';
import {Hash, Settings2, ToggleRight} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import {machinePage} from '@/config/machines';
import type {MachineFormData} from '@/types/machine';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<MachineFormData>;
        errors?: Partial<Record<keyof MachineFormData, string>>;
        processing?: boolean;
        submitLabel?: string;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Machine',
    },
);

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="emit('submit')">
        <!-- Machine Information -->
        <FormSection
            :description="machinePage.form.sectionDescription"
            :title="machinePage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Machine Name -->
                <AppField
                    :error="props.errors.name"
                    :label="machinePage.form.nameLabel"
                    input-id="machine-name"
                    required
                >
                    <AppInput
                        id="machine-name"
                        v-model="props.form.name"
                        :disabled="props.processing"
                        :error="props.errors.name"
                        :icon="Settings2"
                        :placeholder="machinePage.form.namePlaceholder"
                        required
                    />
                </AppField>

                <!-- Machine Number -->
                <AppField
                    :error="props.errors.machine_number"
                    :label="machinePage.form.machineNumberLabel"
                    input-id="machine-machine-number"
                >
                    <AppInput
                        id="machine-machine-number"
                        v-model="props.form.machine_number"
                        :disabled="props.processing"
                        :error="props.errors.machine_number"
                        :icon="Hash"
                        :placeholder="machinePage.form.machineNumberPlaceholder"
                    />
                </AppField>

                <!-- Status -->
                <div class="min-w-0 md:col-span-2">
                    <ToggleCardInput
                        v-model="props.form.status"
                        :disabled="props.processing"
                        :error="props.errors.status"
                        :icon="ToggleRight"
                        active-text="Machine is currently active."
                        inactive-text="Machine is currently inactive."
                        label="Status"
                        title="Machine Status"
                    />
                </div>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :cancel-label="machinePage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
```
