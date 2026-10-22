<script lang="ts" setup>
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

import { operatorWorkLogPage } from '@/config/operatorWorkLogs';

import type {
    OperatorWorkLogFormData,
    OperatorWorkLogPartSetupOption,
} from '@/types/operatorWorkLog';
import { operatorWorkLogSchema } from '@/schemas/operatorWorkLogSchema';
import { useZodFormValidation } from '@/composables/useZodFormValidation';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type LastReport = {
    shift: string | null;
    machine_id: number;
    part_id: number;
    setup_id: number;
};

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        form: InertiaForm<OperatorWorkLogFormData>;
        errors?: Partial<Record<keyof OperatorWorkLogFormData, string>>;
        processing?: boolean;
        submitLabel?: string;
        isCreate: boolean;
        partSetups: OperatorWorkLogPartSetupOption[];
        currentUserId: number;
        canSelectOperator: boolean;
        lastReports: Record<string, LastReport>;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Work Log',
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

/*
|--------------------------------------------------------------------------
| Selected Part Setup
|--------------------------------------------------------------------------
*/

const selectedPartSetup = computed(() => {
    if (!props.form.part_id || !props.form.setup_id) {
        return undefined;
    }

    return props.partSetups.find(
        (partSetup) =>
            partSetup.part_id === props.form.part_id &&
            partSetup.setup_id === props.form.setup_id,
    );
});

/*
|--------------------------------------------------------------------------
| Production Time
|--------------------------------------------------------------------------
|
| Production Time = Quantity × Actual Cycle Time
|--------------------------------------------------------------------------
*/

const productionTimeDisplay = computed(() => {
    const quantity = Number(props.form.quantity) || 0;

    const actualCycleTime = Number(props.form.actual_cycle_time) || 0;

    return formatDuration(quantity * actualCycleTime);
});

/*
|--------------------------------------------------------------------------
| Actual Cycle Time Input
|--------------------------------------------------------------------------
*/

const actualCycleTimeInput = ref(formatDuration(props.form.actual_cycle_time));

/*
|--------------------------------------------------------------------------
| Sync Actual Cycle Time
|--------------------------------------------------------------------------
*/

const syncActualCycleTime = (): void => {
    const seconds = parseDuration(actualCycleTimeInput.value);

    if (seconds === null) {
        actualCycleTimeInput.value = formatDuration(
            props.form.actual_cycle_time,
        );

        return;
    }

    props.form.actual_cycle_time = seconds;

    actualCycleTimeInput.value = formatDuration(seconds);

    props.form.clearErrors('actual_cycle_time');
};

/*
|--------------------------------------------------------------------------
| Apply Part Setup Defaults
|--------------------------------------------------------------------------
*/

const applyPartSetupDefaults = (): void => {
    const partSetup = selectedPartSetup.value;

    /*
     * No matching Part + Setup.
     */
    if (!partSetup) {
        props.form.part_setup_id = null;

        /*
         * Only reset cycle-time values when
         * Part or Setup is actually missing.
         *
         * This prevents a temporary lookup state
         * from unnecessarily destroying the value.
         */
        if (!props.form.part_id || !props.form.setup_id) {
            props.form.machine_cycle_time = 0;

            props.form.actual_cycle_time = 0;

            actualCycleTimeInput.value = '00:00:00';
        }

        return;
    }

    /*
     * Store the PartSetup relation internally.
     */
    props.form.part_setup_id = partSetup.id;

    /*
     * Machine cycle time is required internally
     * but is intentionally not displayed.
     */
    props.form.machine_cycle_time = partSetup.machine_cycle_time_seconds;

    /*
     * Actual cycle time defaults from PartSetup.
     * User can edit this value afterward.
     */
    props.form.actual_cycle_time = partSetup.actual_cycle_time_seconds;

    actualCycleTimeInput.value = formatDuration(
        partSetup.actual_cycle_time_seconds,
    );
};

/*
|--------------------------------------------------------------------------
| Apply Last Report
|--------------------------------------------------------------------------
*/

const applyLastReport = (userId: number | null): void => {
    if (!userId) {
        props.form.shift = null;
        props.form.machine_id = null;
        props.form.part_id = null;
        props.form.setup_id = null;

        applyPartSetupDefaults();

        return;
    }

    const lastReport = props.lastReports[String(userId)];

    if (!lastReport) {
        props.form.shift = null;
        props.form.machine_id = null;
        props.form.part_id = null;
        props.form.setup_id = null;

        applyPartSetupDefaults();

        return;
    }

    /*
     * Restore the operator's last production
     * context for a new work log.
     */
    props.form.shift = lastReport.shift;

    props.form.machine_id = lastReport.machine_id;

    props.form.part_id = lastReport.part_id;

    props.form.setup_id = lastReport.setup_id;

    /*
     * Part + Setup determine the cycle times.
     */
    applyPartSetupDefaults();
};

/*
|--------------------------------------------------------------------------
| Watch Actual Cycle Time
|--------------------------------------------------------------------------
*/

watch(
    () => props.form.actual_cycle_time,
    (seconds) => {
        const formatted = formatDuration(seconds);

        if (actualCycleTimeInput.value !== formatted) {
            actualCycleTimeInput.value = formatted;
        }
    },
);

/*
|--------------------------------------------------------------------------
| Watch Operator
|--------------------------------------------------------------------------
*/

watch(
    () => props.form.user_id,
    (userId, previousUserId) => {
        /*
         * Create mode:
         *
         * Apply the last report for the initial
         * operator. This also handles an operator
         * login where the operator field is hidden.
         */
        if (props.isCreate && previousUserId === undefined) {
            applyLastReport(userId);

            return;
        }

        /*
         * Management users can change the operator
         * while creating a work log.
         */
        if (
            props.isCreate &&
            props.canSelectOperator &&
            previousUserId !== undefined &&
            userId !== previousUserId
        ) {
            applyLastReport(userId);
        }
    },
    {
        immediate: true,
    },
);

/*
|--------------------------------------------------------------------------
| Watch Part + Setup
|--------------------------------------------------------------------------
*/

watch([() => props.form.part_id, () => props.form.setup_id], () => {
    applyPartSetupDefaults();
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatDuration(
    totalSeconds: string | number | null | undefined,
): string {
    const seconds = Math.max(0, Math.floor(Number(totalSeconds) || 0));

    const hours = Math.floor(seconds / 3600);

    const minutes = Math.floor((seconds % 3600) / 60);

    const remainingSeconds = seconds % 60;

    return [
        hours.toString().padStart(2, '0'),

        minutes.toString().padStart(2, '0'),

        remainingSeconds.toString().padStart(2, '0'),
    ].join(':');
}

function parseDuration(value: string): number | null {
    const match = value.trim().match(/^(\d+):([0-5]\d):([0-5]\d)$/);

    if (!match) {
        return null;
    }

    const hours = Number(match[1]);

    const minutes = Number(match[2]);

    const seconds = Number(match[3]);

    return hours * 3600 + minutes * 60 + seconds;
}

// Frontend validation
const { errors: frontendErrors, validate } = useZodFormValidation(
    operatorWorkLogSchema,
    () => props.form,
);

const handleSubmit = () => {
    if (validate()) {
        emit('submit');
    }
};
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <!--
        |--------------------------------------------------------------------------
        | Production Work
        |--------------------------------------------------------------------------
        -->

        <FormSection
            :description="operatorWorkLogPage.form.sectionDescription"
            :title="operatorWorkLogPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Operator -->

                <AppField
                    v-if="canSelectOperator"
                    :label="operatorWorkLogPage.form.operatorLabel"
                    input-id="work-log-operator"
                    required
                >
                    <AppEmployeeSelect
                        v-model="form.user_id"
                        :disabled="processing || !isCreate"
                        :error="frontendErrors.user_id ?? props.errors.user_id"
                        :icon="User"
                        :placeholder="
                            operatorWorkLogPage.form.operatorPlaceholder
                        "
                        role="operator"
                        searchable
                    />
                </AppField>

                <!-- Work Date -->

                <AppField
                    :label="operatorWorkLogPage.form.workDateLabel"
                    input-id="work-log-date"
                    required
                >
                    <AppInput
                        id="work-log-date"
                        v-model="form.work_date"
                        :disabled="processing || !isCreate"
                        :error="
                            frontendErrors.work_date ?? props.errors.work_date
                        "
                        :icon="CalendarDays"
                        :placeholder="
                            operatorWorkLogPage.form.workDatePlaceholder
                        "
                        type="date"
                    />
                </AppField>

                <!-- Shift -->

                <AppField
                    :label="operatorWorkLogPage.form.shiftLabel"
                    input-id="work-log-shift"
                    required
                >
                    <AppUserShiftSelect
                        v-model="form.shift"
                        :disabled="processing || !isCreate"
                        :error="frontendErrors.shift ?? props.errors.shift"
                        :icon="Clock3"
                        :placeholder="operatorWorkLogPage.form.shiftPlaceholder"
                        clearable
                    />
                </AppField>

                <!-- Machine -->

                <AppField
                    :label="operatorWorkLogPage.form.machineLabel"
                    input-id="work-log-machine"
                    required
                >
                    <AppMachineSelect
                        v-model="form.machine_id"
                        :disabled="processing || !isCreate"
                        :error="
                            frontendErrors.machine_id ?? props.errors.machine_id
                        "
                        :icon="Cog"
                        :placeholder="
                            operatorWorkLogPage.form.machinePlaceholder
                        "
                        searchable
                    />
                </AppField>

                <!-- Part -->

                <AppField
                    :label="operatorWorkLogPage.form.partLabel"
                    input-id="work-log-part"
                    required
                >
                    <AppPartSelect
                        v-model="form.part_id"
                        :disabled="processing || !isCreate"
                        :error="frontendErrors.part_id ?? props.errors.part_id"
                        :icon="Wrench"
                        :placeholder="operatorWorkLogPage.form.partPlaceholder"
                        searchable
                    />
                </AppField>

                <!-- Setup -->

                <AppField
                    :label="operatorWorkLogPage.form.setupLabel"
                    input-id="work-log-setup"
                    required
                >
                    <AppSetupSelect
                        v-model="form.setup_id"
                        :disabled="processing || !isCreate"
                        :error="
                            frontendErrors.setup_id ?? props.errors.setup_id
                        "
                        :icon="Settings2"
                        :part-id="form.part_id"
                        :placeholder="operatorWorkLogPage.form.setupPlaceholder"
                        searchable
                    />
                </AppField>

                <!-- Actual Cycle Time -->

                <AppField
                    :label="operatorWorkLogPage.form.actualCycleTimeLabel"
                    input-id="work-log-actual-cycle-time"
                    required
                >
                    <AppInput
                        id="work-log-actual-cycle-time"
                        v-model="actualCycleTimeInput"
                        :disabled="processing || !selectedPartSetup"
                        :error="
                            frontendErrors.actual_cycle_time ??
                            props.errors.actual_cycle_time
                        "
                        :icon="Clock3"
                        :placeholder="
                            operatorWorkLogPage.form
                                .actualCycleTimePlaceholder ?? '00:00:00'
                        "
                        type="text"
                        @blur="syncActualCycleTime"
                    />
                </AppField>

                <!-- Quantity -->

                <AppField
                    :label="operatorWorkLogPage.form.quantityLabel"
                    input-id="work-log-quantity"
                    required
                >
                    <AppInput
                        id="work-log-quantity"
                        v-model.number="form.quantity"
                        :disabled="processing"
                        :error="
                            frontendErrors.quantity ?? props.errors.quantity
                        "
                        :icon="Hash"
                        :placeholder="
                            operatorWorkLogPage.form.quantityPlaceholder
                        "
                        min="1"
                        type="number"
                    />
                </AppField>

                <!-- Rejection Quantity -->

                <AppField
                    :label="operatorWorkLogPage.form.rejectionQuantityLabel"
                    input-id="work-log-rejection-quantity"
                >
                    <AppInput
                        id="work-log-rejection-quantity"
                        v-model.number="form.rejection_quantity"
                        :disabled="processing"
                        :error="
                            frontendErrors.rejection_quantity ??
                            props.errors.rejection_quantity
                        "
                        :icon="Hash"
                        :placeholder="
                            operatorWorkLogPage.form
                                .rejectionQuantityPlaceholder
                        "
                        min="0"
                        type="number"
                    />
                </AppField>

                <!-- Production Time -->

                <AppField
                    :label="operatorWorkLogPage.form.productionTimeLabel"
                    input-id="work-log-production-time"
                >
                    <AppInput
                        id="work-log-production-time"
                        :icon="Clock3"
                        :model-value="productionTimeDisplay"
                        disabled
                        readonly
                    />
                </AppField>
            </div>
        </FormSection>

        <!--
        |--------------------------------------------------------------------------
        | Additional Information
        |--------------------------------------------------------------------------
        -->

        <FormSection
            description="Add any relevant remarks about this work."
            title="Additional Information"
        >
            <AppField
                :label="operatorWorkLogPage.form.remarksLabel"
                input-id="work-log-remarks"
            >
                <AppInput
                    id="work-log-remarks"
                    v-model="form.remarks"
                    :disabled="processing"
                    :error="frontendErrors.remarks ?? props.errors.remarks"
                    :icon="MessageSquare"
                    :placeholder="operatorWorkLogPage.form.remarksPlaceholder"
                />
            </AppField>
        </FormSection>

        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <FormActions
            :cancel-label="operatorWorkLogPage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
