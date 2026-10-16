```vue
<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Hash, Settings2, ToggleRight } from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import { machinePage } from '@/config/machines';
import type { MachineFormData } from '@/types/machine';

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
            :title="machinePage.form.sectionTitle"
            :description="machinePage.form.sectionDescription"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Machine Name -->
                <AppField
                    :label="machinePage.form.nameLabel"
                    input-id="machine-name"
                    :error="props.errors.name"
                    required
                >
                    <AppInput
                        id="machine-name"
                        v-model="props.form.name"
                        :placeholder="machinePage.form.namePlaceholder"
                        :disabled="props.processing"
                        :icon="Settings2"
                        :error="props.errors.name"
                        required
                    />
                </AppField>

                <!-- Machine Number -->
                <AppField
                    :label="machinePage.form.machineNumberLabel"
                    input-id="machine-machine-number"
                    :error="props.errors.machine_number"
                >
                    <AppInput
                        id="machine-machine-number"
                        v-model="props.form.machine_number"
                        :placeholder="machinePage.form.machineNumberPlaceholder"
                        :disabled="props.processing"
                        :icon="Hash"
                        :error="props.errors.machine_number"
                    />
                </AppField>

                <!-- Status -->
                <div class="min-w-0 md:col-span-2">
                    <ToggleCardInput
                        v-model="props.form.status"
                        title="Machine Status"
                        label="Status"
                        active-text="Machine is currently active."
                        inactive-text="Machine is currently inactive."
                        :disabled="props.processing"
                        :error="props.errors.status"
                        :icon="ToggleRight"
                    />
                </div>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :submit-label="submitLabel"
            :processing="processing"
            :cancel-label="machinePage.form.cancelLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
```
