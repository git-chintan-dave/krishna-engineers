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

import { operatorWorkLogPage } from '@/config/operatorWorkLogs';

import type {
    OperatorWorkLogFormData,
    OperatorWorkLogPartSetupOption,
} from '@/types/operatorWorkLog';

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

const props = defineProps<{
    form: InertiaForm<OperatorWorkLogFormData>;
    submitLabel: string;
    processing: boolean;
    isCreate: boolean;
    partSetups: OperatorWorkLogPartSetupOption[];
    currentUserId: number;
    canSelectOperator: boolean;
    lastReports: Record<string, LastReport>;
}>();

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
    if (
        !props.form.part_id ||
        !props.form.setup_id
    ) {
        return undefined;
    }

    return props.partSetups.find(
        (partSetup) =>
            partSetup.part_id ===
            props.form.part_id &&
            partSetup.setup_id ===
            props.form.setup_id,
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
    const quantity =
        Number(props.form.quantity) || 0;

    const actualCycleTime =
        Number(
            props.form.actual_cycle_time,
        ) || 0;

    return formatDuration(
        quantity * actualCycleTime,
    );
});

/*
|--------------------------------------------------------------------------
| Actual Cycle Time Input
|--------------------------------------------------------------------------
*/

const actualCycleTimeInput = ref(
    formatDuration(
        props.form.actual_cycle_time,
    ),
);

/*
|--------------------------------------------------------------------------
| Sync Actual Cycle Time
|--------------------------------------------------------------------------
*/

const syncActualCycleTime = (): void => {
    const seconds = parseDuration(
        actualCycleTimeInput.value,
    );

    if (seconds === null) {
        actualCycleTimeInput.value =
            formatDuration(
                props.form.actual_cycle_time,
            );

        return;
    }

    props.form.actual_cycle_time =
        seconds;

    actualCycleTimeInput.value =
        formatDuration(seconds);

    props.form.clearErrors(
        'actual_cycle_time',
    );
};

/*
|--------------------------------------------------------------------------
| Apply Part Setup Defaults
|--------------------------------------------------------------------------
*/

const applyPartSetupDefaults =
    (): void => {
        const partSetup =
            selectedPartSetup.value;

        /*
         * No matching Part + Setup.
         */
        if (!partSetup) {
            props.form.part_setup_id =
                null;

            /*
             * Only reset cycle-time values when
             * Part or Setup is actually missing.
             *
             * This prevents a temporary lookup state
             * from unnecessarily destroying the value.
             */
            if (
                !props.form.part_id ||
                !props.form.setup_id
            ) {
                props.form.machine_cycle_time =
                    0;

                props.form.actual_cycle_time =
                    0;

                actualCycleTimeInput.value =
                    '00:00:00';
            }

            return;
        }

        /*
         * Store the PartSetup relation internally.
         */
        props.form.part_setup_id =
            partSetup.id;

        /*
         * Machine cycle time is required internally
         * but is intentionally not displayed.
         */
        props.form.machine_cycle_time =
            partSetup.machine_cycle_time_seconds;

        /*
         * Actual cycle time defaults from PartSetup.
         * User can edit this value afterward.
         */
        props.form.actual_cycle_time =
            partSetup.actual_cycle_time_seconds;

        actualCycleTimeInput.value =
            formatDuration(
                partSetup.actual_cycle_time_seconds,
            );
    };

/*
|--------------------------------------------------------------------------
| Apply Last Report
|--------------------------------------------------------------------------
*/

const applyLastReport = (
    userId: number | null,
): void => {
    if (!userId) {
        props.form.shift = null;
        props.form.machine_id = null;
        props.form.part_id = null;
        props.form.setup_id = null;

        applyPartSetupDefaults();

        return;
    }

    const lastReport =
        props.lastReports[String(userId)];

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
    props.form.shift =
        lastReport.shift;

    props.form.machine_id =
        lastReport.machine_id;

    props.form.part_id =
        lastReport.part_id;

    props.form.setup_id =
        lastReport.setup_id;

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
    () =>
        props.form.actual_cycle_time,
    (seconds) => {
        const formatted =
            formatDuration(seconds);

        if (
            actualCycleTimeInput.value !==
            formatted
        ) {
            actualCycleTimeInput.value =
                formatted;
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
    (
        userId,
        previousUserId,
    ) => {
        /*
         * Create mode:
         *
         * Apply the last report for the initial
         * operator. This also handles an operator
         * login where the operator field is hidden.
         */
        if (
            props.isCreate &&
            previousUserId === undefined
        ) {
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

watch(
    [
        () => props.form.part_id,
        () => props.form.setup_id,
    ],
    () => {
        applyPartSetupDefaults();
    },
);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatDuration(
    totalSeconds:
        | number
        | null
        | undefined,
): string {
    const seconds = Math.max(
        0,
        Math.floor(
            Number(totalSeconds) || 0,
        ),
    );

    const hours =
        Math.floor(
            seconds / 3600,
        );

    const minutes =
        Math.floor(
            (seconds % 3600) / 60,
        );

    const remainingSeconds =
        seconds % 60;

    return [
        hours
            .toString()
            .padStart(2, '0'),

        minutes
            .toString()
            .padStart(2, '0'),

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

    const hours =
        Number(match[1]);

    const minutes =
        Number(match[2]);

    const seconds =
        Number(match[3]);

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
        @submit.prevent="
            emit('submit')
        "
    >
        <!--
        |--------------------------------------------------------------------------
        | Production Work
        |--------------------------------------------------------------------------
        -->

        <FormSection
            :title="
                operatorWorkLogPage.form
                    .sectionTitle
            "
            :description="
                operatorWorkLogPage.form
                    .sectionDescription
            "
        >
            <div
                class="grid min-w-0 gap-5 md:grid-cols-2"
            >
                <!-- Operator -->

                <AppField
                    v-if="canSelectOperator"
                    :label="
                        operatorWorkLogPage.form
                            .operatorLabel
                    "
                    input-id="work-log-operator"
                    :error="
                        form.errors.user_id
                    "
                    required
                >
                    <AppEmployeeSelect
                        v-model="form.user_id"
                        :placeholder="
                            operatorWorkLogPage.form
                                .operatorPlaceholder
                        "
                        :disabled="
                            processing ||
                            !isCreate
                        "
                        :error="
                            form.errors.user_id
                        "
                        :icon="User"
                        searchable
                        role="operator"
                    />
                </AppField>

                <!-- Work Date -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .workDateLabel
                    "
                    input-id="work-log-date"
                    :error="
                        form.errors.work_date
                    "
                    required
                >
                    <AppInput
                        id="work-log-date"
                        v-model="form.work_date"
                        type="date"
                        :placeholder="
                            operatorWorkLogPage.form
                                .workDatePlaceholder
                        "
                        :disabled="
                            processing ||
                            !isCreate
                        "
                        :error="
                            form.errors.work_date
                        "
                        :icon="CalendarDays"
                        required
                    />
                </AppField>

                <!-- Shift -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .shiftLabel
                    "
                    input-id="work-log-shift"
                    :error="
                        form.errors.shift
                    "
                    required
                >
                    <AppUserShiftSelect
                        v-model="form.shift"
                        :placeholder="
                            operatorWorkLogPage.form
                                .shiftPlaceholder
                        "
                        :disabled="
                            processing ||
                            !isCreate
                        "
                        :error="
                            form.errors.shift
                        "
                        :icon="Clock3"
                        clearable
                    />
                </AppField>

                <!-- Machine -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .machineLabel
                    "
                    input-id="work-log-machine"
                    :error="
                        form.errors.machine_id
                    "
                    required
                >
                    <AppMachineSelect
                        v-model="form.machine_id"
                        :placeholder="
                            operatorWorkLogPage.form
                                .machinePlaceholder
                        "
                        :disabled="
                            processing ||
                            !isCreate
                        "
                        :error="
                            form.errors.machine_id
                        "
                        :icon="Cog"
                        searchable
                    />
                </AppField>

                <!-- Part -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .partLabel
                    "
                    input-id="work-log-part"
                    :error="
                        form.errors.part_id
                    "
                    required
                >
                    <AppPartSelect
                        v-model="form.part_id"
                        :placeholder="
                            operatorWorkLogPage.form
                                .partPlaceholder
                        "
                        :disabled="
                            processing ||
                            !isCreate
                        "
                        :error="
                            form.errors.part_id
                        "
                        :icon="Wrench"
                        searchable
                    />
                </AppField>

                <!-- Setup -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .setupLabel
                    "
                    input-id="work-log-setup"
                    :error="
                        form.errors.setup_id
                    "
                    required
                >
                    <AppSetupSelect
                        v-model="form.setup_id"
                        :part-id="form.part_id"
                        :placeholder="
                            operatorWorkLogPage.form
                                .setupPlaceholder
                        "
                        :disabled="
                            processing ||
                            !isCreate
                        "
                        :error="
                            form.errors.setup_id
                        "
                        :icon="Settings2"
                        searchable
                    />
                </AppField>

                <!-- Actual Cycle Time -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .actualCycleTimeLabel
                    "
                    input-id="work-log-actual-cycle-time"
                    :error="
                        form.errors
                            .actual_cycle_time
                    "
                    required
                >
                    <AppInput
                        id="work-log-actual-cycle-time"
                        v-model="
                            actualCycleTimeInput
                        "
                        type="text"
                        :placeholder="
                            operatorWorkLogPage.form
                                .actualCycleTimePlaceholder ??
                            '00:00:00'
                        "
                        :disabled="
                            processing ||
                            !selectedPartSetup
                        "
                        :error="
                            form.errors
                                .actual_cycle_time
                        "
                        :icon="Clock3"
                        required
                        @blur="
                            syncActualCycleTime
                        "
                    />
                </AppField>

                <!-- Quantity -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .quantityLabel
                    "
                    input-id="work-log-quantity"
                    :error="
                        form.errors.quantity
                    "
                    required
                >
                    <AppInput
                        id="work-log-quantity"
                        v-model.number="
                            form.quantity
                        "
                        type="number"
                        min="1"
                        :placeholder="
                            operatorWorkLogPage.form
                                .quantityPlaceholder
                        "
                        :disabled="processing"
                        :error="
                            form.errors.quantity
                        "
                        :icon="Hash"
                        required
                    />
                </AppField>

                <!-- Rejection Quantity -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .rejectionQuantityLabel
                    "
                    input-id="work-log-rejection-quantity"
                    :error="
                        form.errors
                            .rejection_quantity
                    "
                >
                    <AppInput
                        id="work-log-rejection-quantity"
                        v-model.number="
                            form.rejection_quantity
                        "
                        type="number"
                        min="0"
                        :placeholder="
                            operatorWorkLogPage.form
                                .rejectionQuantityPlaceholder
                        "
                        :disabled="processing"
                        :error="
                            form.errors
                                .rejection_quantity
                        "
                        :icon="Hash"
                    />
                </AppField>

                <!-- Production Time -->

                <AppField
                    :label="
                        operatorWorkLogPage.form
                            .productionTimeLabel
                    "
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

        <!--
        |--------------------------------------------------------------------------
        | Additional Information
        |--------------------------------------------------------------------------
        -->

        <FormSection
            title="Additional Information"
            description="Add any relevant remarks about this work."
        >
            <AppField
                :label="
                    operatorWorkLogPage.form
                        .remarksLabel
                "
                input-id="work-log-remarks"
                :error="
                    form.errors.remarks
                "
            >
                <AppInput
                    id="work-log-remarks"
                    v-model="form.remarks"
                    :placeholder="
                        operatorWorkLogPage.form
                            .remarksPlaceholder
                    "
                    :disabled="processing"
                    :error="
                        form.errors.remarks
                    "
                    :icon="MessageSquare"
                />
            </AppField>
        </FormSection>

        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <FormActions
            :submit-label="submitLabel"
            :processing="processing"
            :cancel-label="
                operatorWorkLogPage.form
                    .cancelLabel
            "
            @cancel="emit('cancel')"
        />
    </form>
</template>
