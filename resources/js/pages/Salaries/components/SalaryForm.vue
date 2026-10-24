<script lang="ts" setup>
import axios from 'axios';
import type {InertiaForm} from '@inertiajs/vue3';
import {CalendarDays, IndianRupee, User} from '@lucide/vue';
import {computed, ref, watch} from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AttendanceHistoryDialog from '@/components/attendance/AttendanceHistoryDialog.vue';
import {Button} from '@/components/ui/button';
import {useZodFormValidation} from '@/composables/useZodFormValidation';
import {salaryPage} from '@/config/salaries';
import {preview as previewSalary} from '@/routes/salaries';
import {salarySchema} from '@/schemas/salarySchema';
import type {Salary, SalaryFormData} from '@/types/salary';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SalaryPreview = {
    base_salary: string | number;
    attendance_days: number;
    total_undertime_seconds: number;
    calculated_late_days: number;
    late_days: number;
    working_days: number;
    incentive_rate: string | number;
    incentive: string | number;
    deduction_rate: string | number;
    deduction: string | number;
    final_payable: string | number;
};

type SalaryField = keyof SalaryFormData;

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        form: InertiaForm<SalaryFormData>;
        salary?: Salary;
        errors?: Partial<Record<SalaryField, string>>;
        processing?: boolean;
        submitLabel?: string;
    }>(),
    {
        salary: undefined,
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Salary',
    },
);

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const previewData = ref<SalaryPreview | null>(null);
const previewLoading = ref(false);
const attendanceDialogOpen = ref(false);

let previewRequestId = 0;

const isEdit = computed(() => Boolean(props.salary));

/*
|--------------------------------------------------------------------------
| Salary Calculation
|--------------------------------------------------------------------------
*/

const baseSalary = computed(() =>
    Number(props.salary?.base_salary ?? previewData.value?.base_salary ?? 0),
);

const attendanceDays = computed(() =>
    Number(
        props.salary?.attendance_days ??
        previewData.value?.attendance_days ??
        0,
    ),
);

const totalUndertimeSeconds = computed(() =>
    Number(
        props.salary?.total_undertime_seconds ??
        previewData.value?.total_undertime_seconds ??
        0,
    ),
);

const calculatedLateDays = computed(() =>
    Math.floor(totalUndertimeSeconds.value / (12 * 60 * 60)),
);

const lateDays = computed(() => Number(props.form.late_days || 0));

const workingDays = computed(() =>
    Math.max(0, attendanceDays.value - lateDays.value),
);

const incentiveRate = computed(() =>
    Number(
        props.salary?.incentive_rate ??
        previewData.value?.incentive_rate ??
        0,
    ),
);

const deductionRate = computed(() =>
    Number(
        props.salary?.deduction_rate ??
        previewData.value?.deduction_rate ??
        0,
    ),
);

const incentive = computed(() =>
    workingDays.value <= 23
        ? 0
        : (workingDays.value - 23) * incentiveRate.value,
);

const deduction = computed(() =>
    workingDays.value >= 21
        ? 0
        : (21 - workingDays.value) * deductionRate.value,
);

const upad = computed(() => Number(props.form.upad || 0));
const paidLoan = computed(() => Number(props.form.paid_loan || 0));
const adjustment = computed(() => Number(props.form.adjustment || 0));

const finalPayable = computed(() =>
    (
        baseSalary.value * workingDays.value +
        incentive.value +
        adjustment.value -
        upad.value -
        paidLoan.value -
        deduction.value
    ).toFixed(2),
);

const hasAdjustment = computed(() => adjustment.value > 0);

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

const formatCurrency = (value: number | string): string =>
    Number(value || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const formatDuration = (seconds: number): string => {
    const totalMinutes = Math.floor(Math.max(0, seconds) / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    return `${hours}h ${String(minutes).padStart(2, '0')}m`;
};

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

const { errors: frontendErrors, validate } = useZodFormValidation(
    salarySchema,
    () => props.form,
);

const handleSubmit = (): void => {
    if (validate()) {
        emit('submit');
    }
};

/*
|--------------------------------------------------------------------------
| Preview
|--------------------------------------------------------------------------
*/

const loadPreview = async (): Promise<void> => {
    if (
        isEdit.value ||
        !props.form.user_id ||
        !props.form.salary_period_start ||
        !props.form.salary_period_end
    ) {
        previewRequestId++;
        previewData.value = null;
        previewLoading.value = false;

        return;
    }

    const requestId = ++previewRequestId;

    previewLoading.value = true;

    try {
        const response = await axios.get<SalaryPreview>(
            previewSalary().url,
            {
                params: {
                    user_id: props.form.user_id,
                    salary_period_start: props.form.salary_period_start,
                    salary_period_end: props.form.salary_period_end,
                },
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        if (requestId !== previewRequestId) {
            return;
        }

        previewData.value = response.data;

        // Initialize editable late days from the server calculation.
        props.form.late_days = response.data.late_days;
    } catch {
        if (requestId === previewRequestId) {
            previewData.value = null;
        }
    } finally {
        if (requestId === previewRequestId) {
            previewLoading.value = false;
        }
    }
};

/*
|--------------------------------------------------------------------------
| Period Handling
|--------------------------------------------------------------------------
*/

const setPeriodEndFromStart = (): void => {
    if (isEdit.value || !props.form.salary_period_start) {
        return;
    }

    const [year, month, day] = props.form.salary_period_start
        .split('-')
        .map(Number);

    const startDate = new Date(year, month - 1, day);

    if (
        startDate.getFullYear() !== year ||
        startDate.getMonth() !== month - 1 ||
        startDate.getDate() !== day
    ) {
        return;
    }

    const endDate = new Date(year, month, 0);

    props.form.salary_period_end = [
        endDate.getFullYear(),
        String(endDate.getMonth() + 1).padStart(2, '0'),
        String(endDate.getDate()).padStart(2, '0'),
    ].join('-');
};

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(() => props.form.salary_period_start, setPeriodEndFromStart);

watch(
    [
        () => props.form.user_id,
        () => props.form.salary_period_start,
        () => props.form.salary_period_end,
    ],
    loadPreview,
    { immediate: true },
);

watch(
    () => props.salary?.late_days,
    (value) => {
        if (isEdit.value && value !== undefined && value !== null) {
            props.form.late_days = value;
        }
    },
    { immediate: true },
);
</script>

<template>
    <form class="min-w-0 space-y-6" novalidate @submit.prevent="handleSubmit">
        <!-- Employee and salary period -->
        <FormSection
            :description="salaryPage.form.sectionDescription"
            :title="salaryPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <AppField
                    :label="salaryPage.form.employeeLabel"
                    input-id="salary-employee"
                    required
                >
                    <AppEmployeeSelect
                        v-model="props.form.user_id"
                        :disabled="processing || isEdit"
                        :error="frontendErrors.user_id ?? props.errors.user_id"
                        :icon="User"
                        :placeholder="salaryPage.form.employeePlaceholder"
                        searchable
                    />
                </AppField>

                <AppField
                    :label="salaryPage.form.salaryPeriodStartLabel"
                    input-id="salary-period-start"
                    required
                >
                    <AppInput
                        id="salary-period-start"
                        v-model="props.form.salary_period_start"
                        :disabled="processing || isEdit"
                        :error="
                            frontendErrors.salary_period_start ??
                            props.errors.salary_period_start
                        "
                        :icon="CalendarDays"
                        :placeholder="
                            salaryPage.form.salaryPeriodStartPlaceholder
                        "
                        type="date"
                    />
                </AppField>

                <AppField
                    :label="salaryPage.form.salaryPeriodEndLabel"
                    input-id="salary-period-end"
                    required
                >
                    <AppInput
                        id="salary-period-end"
                        v-model="props.form.salary_period_end"
                        :disabled="processing || isEdit"
                        :error="
                            frontendErrors.salary_period_end ??
                            props.errors.salary_period_end
                        "
                        :icon="CalendarDays"
                        :placeholder="
                            salaryPage.form.salaryPeriodEndPlaceholder
                        "
                        type="date"
                    />
                </AppField>
            </div>

            <!-- Attendance history dialog -->
            <div
                class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t pt-4"
            >
                <p class="text-sm text-muted-foreground">
                    Review attendance records for this salary period.
                </p>

                <Button
                    type="button"
                    variant="outline"
                    :disabled="
                        !props.form.user_id ||
                        !props.form.salary_period_start ||
                        !props.form.salary_period_end
                    "
                    @click="attendanceDialogOpen = true"
                >
                    <CalendarDays class="mr-2 size-4" />
                    View Attendance
                </Button>
            </div>
        </FormSection>

        <AttendanceHistoryDialog
            v-model:open="attendanceDialogOpen"
            :user-id="props.form.user_id"
            :start-date="props.form.salary_period_start"
            :end-date="props.form.salary_period_end"
        />

        <!-- Main content -->
        <div
            class="grid min-w-0 items-start gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(280px,1fr)]"
        >
            <div class="min-w-0 space-y-6">
                <!-- Payment Adjustments -->
                <FormSection
                    description="Enter any advance, loan payment, or manual salary adjustment."
                    title="Payment Adjustments"
                >
                    <div class="grid min-w-0 gap-5 md:grid-cols-2">
                        <AppField
                            :label="salaryPage.form.upadLabel"
                            input-id="salary-upad"
                            required
                        >
                            <AppInput
                                id="salary-upad"
                                v-model="props.form.upad"
                                :disabled="processing"
                                :error="frontendErrors.upad ?? props.errors.upad"
                                :icon="IndianRupee"
                                :placeholder="salaryPage.form.upadPlaceholder"
                                min="0"
                                step="0.01"
                                type="number"
                            />
                        </AppField>

                        <AppField
                            :label="salaryPage.form.paidLoanLabel"
                            input-id="salary-paid-loan"
                            required
                        >
                            <AppInput
                                id="salary-paid-loan"
                                v-model="props.form.paid_loan"
                                :disabled="processing"
                                :error="
                                    frontendErrors.paid_loan ??
                                    props.errors.paid_loan
                                "
                                :icon="IndianRupee"
                                :placeholder="salaryPage.form.paidLoanPlaceholder"
                                min="0"
                                step="0.01"
                                type="number"
                            />
                        </AppField>

                        <AppField
                            :label="salaryPage.form.adjustmentLabel"
                            input-id="salary-adjustment"
                            required
                        >
                            <AppInput
                                id="salary-adjustment"
                                v-model="props.form.adjustment"
                                :disabled="processing"
                                :error="
                                    frontendErrors.adjustment ??
                                    props.errors.adjustment
                                "
                                :icon="IndianRupee"
                                :placeholder="salaryPage.form.adjustmentPlaceholder"
                                step="0.01"
                                type="number"
                            />
                        </AppField>

                        <AppField
                            :label="salaryPage.form.adjustmentReasonLabel"
                            :required="hasAdjustment"
                            input-id="salary-adjustment-reason"
                        >
                            <AppInput
                                id="salary-adjustment-reason"
                                v-model="props.form.adjustment_reason"
                                :disabled="processing"
                                :error="
                                    frontendErrors.adjustment_reason ??
                                    props.errors.adjustment_reason
                                "
                                :placeholder="
                                    salaryPage.form.adjustmentReasonPlaceholder
                                "
                            />
                        </AppField>
                    </div>
                </FormSection>

                <!-- Salary Summary -->
                <FormSection
                    description="The amount updates automatically as you change the adjustments or late days."
                    title="Salary Summary"
                >
                    <div class="rounded-xl border bg-muted/30 p-5">
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-medium">
                                    {{ salaryPage.form.finalPayableLabel }}
                                </p>

                                <p class="mt-1 text-sm text-muted-foreground">
                                    Base salary × payable days + incentive +
                                    adjustment − UPAD − paid loan − deduction.
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <IndianRupee
                                    aria-hidden="true"
                                    class="size-5 text-muted-foreground"
                                />

                                <p class="text-2xl font-semibold tabular-nums">
                                    {{ formatCurrency(finalPayable) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </FormSection>
            </div>

            <!-- Attendance and Salary Summary -->
            <aside class="min-w-0 space-y-4 lg:sticky lg:top-6">
                <div class="rounded-xl border bg-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">
                                Attendance & Salary
                            </h3>

                            <p class="mt-1 text-sm text-muted-foreground">
                                Review the calculation for this salary period.
                            </p>
                        </div>

                        <IndianRupee
                            aria-hidden="true"
                            class="size-5 shrink-0 text-muted-foreground"
                        />
                    </div>

                    <div
                        v-if="previewLoading"
                        class="mt-5 animate-pulse space-y-4"
                    >
                        <div class="h-4 rounded bg-muted" />
                        <div class="h-4 rounded bg-muted" />
                        <div class="h-4 rounded bg-muted" />
                    </div>

                    <div v-else class="mt-5 space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-muted-foreground">
                                Base salary / day
                            </span>
                            <span class="font-medium tabular-nums">
                                ₹{{ formatCurrency(baseSalary) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-muted-foreground">
                                Attendance days
                            </span>
                            <span class="font-medium tabular-nums">
                                {{ attendanceDays }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-muted-foreground">
                                Total undertime
                            </span>
                            <span class="font-medium tabular-nums">
                                {{ formatDuration(totalUndertimeSeconds) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-muted-foreground">
                                Suggested late days
                            </span>
                            <span class="font-medium tabular-nums">
                                {{ calculatedLateDays }}
                            </span>
                        </div>

                        <div class="border-t pt-4">
                            <AppField
                                label="Late days deducted"
                                input-id="salary-late-days"
                                required
                            >
                                <AppInput
                                    id="salary-late-days"
                                    v-model="props.form.late_days"
                                    :disabled="processing"
                                    :error="
                                        frontendErrors.late_days ??
                                        props.errors.late_days
                                    "
                                    min="0"
                                    :max="attendanceDays"
                                    step="1"
                                    type="number"
                                />
                            </AppField>

                            <p class="mt-2 text-xs text-muted-foreground">
                                Automatically calculated from undertime. You
                                can adjust this value before saving.
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 rounded-lg bg-muted/50 p-3"
                        >
                            <span class="text-sm font-medium">
                                Payable working days
                            </span>
                            <span class="text-lg font-semibold tabular-nums">
                                {{ workingDays }}
                            </span>
                        </div>

                        <div class="space-y-3 border-t pt-4">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-muted-foreground">
                                    Base salary
                                </span>
                                <span class="tabular-nums">
                                    ₹{{ formatCurrency(baseSalary * workingDays) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-muted-foreground">
                                    Incentive
                                </span>
                                <span class="tabular-nums">
                                    ₹{{ formatCurrency(incentive) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-muted-foreground">
                                    Deduction
                                </span>
                                <span class="tabular-nums">
                                    − ₹{{ formatCurrency(deduction) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <FormActions
            :cancel-label="salaryPage.form.cancelLabel"
            :processing="processing || previewLoading"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
