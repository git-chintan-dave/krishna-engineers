<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    Clock3,
    Cog,
    Hash,
    MessageSquare,
    Settings2,
    User,
    Wrench,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppMachineSelect from '@/components/selects/AppMachineSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import AppUserShiftSelect from '@/components/selects/AppUserShiftSelect.vue';
import type { OperatorWorkLogFormData } from '@/types/operatorWorkLog';

// Types
type PartSetupOption = {
    id: number;
    part_id: number;
    setup_id: number;
    machine_cycle_time_seconds: number;
    actual_cycle_time_seconds: number;
};

type LastReport = {
    shift: string | null;
    machine_id: number;
    part_id: number;
    setup_id: number;
};

// Props
const props = defineProps<{
    form: InertiaForm<OperatorWorkLogFormData>;
    submitLabel: string;
    processing: boolean;
    isCreate: boolean;
    partSetups: PartSetupOption[];
    currentUserId: number;
    canSelectOperator: boolean;
    lastReports: Record<string, LastReport>;
}>();

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

// Computed
const selectedPartSetup = computed(() =>
    props.partSetups.find(
        (partSetup) =>
            partSetup.part_id === props.form.part_id &&
            partSetup.setup_id === props.form.setup_id,
    ),
);

const machineCycleTimeDisplay = computed(() =>
    formatDuration(
        props.form.machine_cycle_time,
    ),
);

const productionTimeDisplay = computed(() => {
    const quantity =
        Number(props.form.quantity) || 0;

    const cycleTime =
        Number(props.form.actual_cycle_time) || 0;

    return formatDuration(
        quantity * cycleTime,
    );
});

// State
const actualCycleTimeInput = ref(
    formatDuration(
        props.form.actual_cycle_time,
    ),
);

// Cycle Time
const syncActualCycleTime = (): void => {
    const seconds = parseDuration(
        actualCycleTimeInput.value,
    );

    if (seconds === null) {
        actualCycleTimeInput.value = formatDuration(
            props.form.actual_cycle_time,
        );

        return;
    }

    props.form.actual_cycle_time = seconds;
    actualCycleTimeInput.value =
        formatDuration(seconds);
};

const applyPartSetupDefaults = (): void => {
    const partSetup = selectedPartSetup.value;

    if (!partSetup) {
        props.form.part_setup_id = null;
        props.form.machine_cycle_time = 0;
        props.form.actual_cycle_time = 0;
        actualCycleTimeInput.value = '00:00:00';

        return;
    }

    props.form.part_setup_id = partSetup.id;
    props.form.machine_cycle_time =
        partSetup.machine_cycle_time_seconds;

    props.form.actual_cycle_time =
        partSetup.actual_cycle_time_seconds;

    actualCycleTimeInput.value = formatDuration(
        partSetup.actual_cycle_time_seconds,
    );
};

// Last Report
const applyLastReport = (
    userId: number | null,
): void => {
    const lastReport = userId
        ? props.lastReports[String(userId)]
        : undefined;

    if (!lastReport) {
        props.form.shift = null;
        props.form.machine_id = null;
        props.form.part_id = null;
        props.form.setup_id = null;

        applyPartSetupDefaults();

        return;
    }

    props.form.shift = lastReport.shift;
    props.form.machine_id =
        lastReport.machine_id;
    props.form.part_id = lastReport.part_id;
    props.form.setup_id = lastReport.setup_id;

    applyPartSetupDefaults();
};

// Watchers
watch(
    () => props.form.actual_cycle_time,
    (seconds) => {
        actualCycleTimeInput.value =
            formatDuration(seconds);
    },
);

watch(
    () => props.form.user_id,
    (userId, previousUserId) => {
        if (!props.canSelectOperator) {
            return;
        }

        if (
            props.isCreate &&
            previousUserId === undefined
        ) {
            applyLastReport(userId);
            return;
        }

        if (
            previousUserId !== undefined &&
            userId !== previousUserId
        ) {
            applyLastReport(userId);
        }
    },
    { immediate: true },
);

watch(
    [
        () => props.form.part_id,
        () => props.form.setup_id,
    ],
    () => {
        applyPartSetupDefaults();
    },
);

// Helpers
function formatDuration(
    totalSeconds: number | null | undefined,
): string {
    const seconds = Math.max(
        0,
        Math.floor(Number(totalSeconds) || 0),
    );

    const hours = Math.floor(
        seconds / 3600,
    );

    const minutes = Math.floor(
        (seconds % 3600) / 60,
    );

    const remainingSeconds =
        seconds % 60;

    return [
        hours.toString().padStart(2, '0'),
        minutes.toString().padStart(2, '0'),
        remainingSeconds
            .toString()
            .padStart(2, '0'),
    ].join(':');
}

function parseDuration(
    value: string,
): number | null {
    const match = value
        .trim()
        .match(
            /^(\d+):([0-5]\d):([0-5]\d)$/,
        );

    if (!match) {
        return null;
    }

    const hours = Number(match[1]);
    const minutes = Number(match[2]);
    const seconds = Number(match[3]);

    return (
        hours * 3600 +
        minutes * 60 +
        seconds
    );
}
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        @submit.prevent="emit('submit')"
    >
        <!-- Production Work -->
        <FormSection
            title="Production Work"
            description="Enter the production work completed by the operator."
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Operator -->
                <AppField
                    v-if="canSelectOperator"
                    label="Operator"
                    input-id="work-log-operator"
                    :error="form.errors.user_id"
                    required
                >
                    <AppEmployeeSelect
                        v-model="form.user_id"
                        placeholder="Select operator"
                        :disabled="processing"
                        :error="form.errors.user_id"
                        :icon="User"
                        searchable
                        role="operator"
                    />
                </AppField>

                <!-- Work Date -->
                <AppField
                    label="Work Date"
                    input-id="work-log-date"
                    :error="form.errors.work_date"
                    required
                >
                    <AppInput
                        id="work-log-date"
                        v-model="form.work_date"
                        type="date"
                        :disabled="processing"
                        :error="form.errors.work_date"
                        :icon="CalendarDays"
                        required
                    />
                </AppField>

                <!-- Shift -->
                <AppField
                    label="Shift"
                    input-id="work-log-shift"
                    :error="form.errors.shift"
                >
                    <AppUserShiftSelect
                        v-model="form.shift"
                        placeholder="Select shift"
                        :disabled="processing"
                        :error="form.errors.shift"
                        :icon="Clock3"
                        clearable
                    />
                </AppField>

                <!-- Machine -->
                <AppField
                    label="Machine"
                    input-id="work-log-machine"
                    :error="form.errors.machine_id"
                    required
                >
                    <AppMachineSelect
                        v-model="form.machine_id"
                        placeholder="Select machine"
                        :disabled="processing"
                        :error="form.errors.machine_id"
                        :icon="Cog"
                        searchable
                    />
                </AppField>

                <!-- Part -->
                <AppField
                    label="Part"
                    input-id="work-log-part"
                    :error="form.errors.part_id"
                    required
                >
                    <AppPartSelect
                        v-model="form.part_id"
                        placeholder="Select part"
                        :disabled="processing"
                        :error="form.errors.part_id"
                        :icon="Wrench"
                        searchable
                    />
                </AppField>

                <!-- Setup -->
                <AppField
                    label="Setup"
                    input-id="work-log-setup"
                    :error="form.errors.setup_id"
                    required
                >
                    <AppSetupSelect
                        v-model="form.setup_id"
                        placeholder="Select setup"
                        :disabled="processing"
                        :error="form.errors.setup_id"
                        :icon="Settings2"
                        searchable
                    />
                </AppField>

                <!-- Machine Cycle Time -->
                <AppField
                    label="Machine Cycle Time"
                    input-id="work-log-machine-cycle-time"
                    :error="
                        form.errors.machine_cycle_time
                    "
                >
                    <AppInput
                        id="work-log-machine-cycle-time"
                        :model-value="
                            machineCycleTimeDisplay
                        "
                        :icon="Clock3"
                        readonly
                        disabled
                    />
                </AppField>

                <!-- Actual Cycle Time -->
                <AppField
                    label="Actual Cycle Time"
                    input-id="work-log-actual-cycle-time"
                    :error="
                        form.errors.actual_cycle_time
                    "
                    required
                >
                    <AppInput
                        id="work-log-actual-cycle-time"
                        v-model="actualCycleTimeInput"
                        type="text"
                        placeholder="00:00:00"
                        :disabled="processing"
                        :error="
                            form.errors.actual_cycle_time
                        "
                        :icon="Clock3"
                        required
                        @blur="syncActualCycleTime"
                    />
                </AppField>

                <!-- Quantity -->
                <AppField
                    label="Quantity"
                    input-id="work-log-quantity"
                    :error="form.errors.quantity"
                    required
                >
                    <AppInput
                        id="work-log-quantity"
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        :disabled="processing"
                        :error="form.errors.quantity"
                        :icon="Hash"
                        required
                    />
                </AppField>

                <!-- Rejection Quantity -->
                <AppField
                    label="Rejection Quantity"
                    input-id="work-log-rejection-quantity"
                    :error="
                        form.errors.rejection_quantity
                    "
                >
                    <AppInput
                        id="work-log-rejection-quantity"
                        v-model.number="
                            form.rejection_quantity
                        "
                        type="number"
                        min="0"
                        :disabled="processing"
                        :error="
                            form.errors.rejection_quantity
                        "
                        :icon="Hash"
                    />
                </AppField>

                <!-- Calculated Production Time -->
                <AppField
                    label="Calculated Production Time"
                    input-id="work-log-production-time"
                >
                    <AppInput
                        id="work-log-production-time"
                        :model-value="
                            productionTimeDisplay
                        "
                        :icon="Clock3"
                        readonly
                        disabled
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Additional Information -->
        <FormSection
            title="Additional Information"
            description="Add any relevant remarks about this work."
        >
            <AppField
                label="Remarks"
                input-id="work-log-remarks"
                :error="form.errors.remarks"
            >
                <AppInput
                    id="work-log-remarks"
                    v-model="form.remarks"
                    placeholder="Enter remarks"
                    :disabled="processing"
                    :error="form.errors.remarks"
                    :icon="MessageSquare"
                />
            </AppField>
        </FormSection>

        <!-- Form Actions -->
        <FormActions
            :submit-label="submitLabel"
            :processing="processing"
            cancel-label="Cancel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
