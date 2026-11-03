<script lang="ts" setup>
import axios from 'axios';
import type {InertiaForm} from '@inertiajs/vue3';
import {CalendarDays, IndianRupee, User} from '@lucide/vue';
import {computed, ref, watch} from 'vue';

import AttendanceHistoryDialog from '@/components/attendance/AttendanceHistoryDialog.vue';
import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import {Button} from '@/components/ui/button';
import {useZodFormValidation} from '@/composables/useZodFormValidation';
import {salaryPage} from '@/config/salaries';
import {preview as previewSalary} from '@/routes/salaries';
import {salarySchema} from '@/schemas/salarySchema';
import type {Salary, SalaryFormData} from '@/types/salary';

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

    bonus: string | number;

    final_payable: string | number;

    show_production_efficiency: boolean;
    production_efficiency: number | null;
};

type SalaryField = keyof SalaryFormData;

const props = withDefaults(
    defineProps<{
        form: InertiaForm<SalaryFormData>;
        salary?: Salary;
        errors?: Partial<Record<SalaryField, string>>;
        processing?: boolean;
        submitLabel?: string;
        showProductionEfficiency?: boolean;
        productionEfficiency?: number | null;
    }>(),
    {
        salary: undefined,
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Salary',
        showProductionEfficiency: false,
        productionEfficiency: null,
    },
);

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

const previewData = ref<SalaryPreview | null>(null);
const previewLoading = ref(false);
const attendanceDialogOpen = ref(false);

let previewRequestId = 0;

const isEdit = computed(() => Boolean(props.salary));

/*
|--------------------------------------------------------------------------
| Salary Month
|--------------------------------------------------------------------------
*/

const salaryMonth = computed({
    get: () => props.form.salary_period_start.slice(0, 7),

    set: (month: string) => {
        if (!month) {
            props.form.salary_period_start = '';
            props.form.salary_period_end = '';
            return;
        }

        const [year, monthNumber] = month.split('-').map(Number);

        if (
            !year ||
            !monthNumber ||
            monthNumber < 1 ||
            monthNumber > 12
        ) {
            return;
        }

        const startDate = new Date(year, monthNumber - 1, 1);
        const endDate = new Date(year, monthNumber, 0);

        props.form.salary_period_start = formatDate(startDate);
        props.form.salary_period_end = formatDate(endDate);
    },
});

const formatDate = (date: Date): string =>
    [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');

/*
|--------------------------------------------------------------------------
| Production Efficiency
|--------------------------------------------------------------------------
*/

const showProductionEfficiency = computed(() =>
    props.salary
        ? props.showProductionEfficiency
        : (previewData.value?.show_production_efficiency ?? false),
);

const productionEfficiency = computed(() =>
    props.salary
        ? props.productionEfficiency
        : (previewData.value?.production_efficiency ?? null),
);

/*
|--------------------------------------------------------------------------
| Attendance & Salary Calculations
|--------------------------------------------------------------------------
*/

const baseSalary = computed(() =>
    Number(
        props.salary?.base_salary ??
        previewData.value?.base_salary ??
        0,
    ),
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
    Math.min(
        attendanceDays.value,
        Math.floor(totalUndertimeSeconds.value / (12 * 60 * 60)),
    ),
);

const lateDays = computed(() =>
    Math.max(0, Number(props.form.late_days || 0)),
);

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

// These values are editable and are used directly in the final calculation.
const incentive = computed(() =>
    Number(props.form.incentive || 0),
);

const deduction = computed(() =>
    Number(props.form.deduction || 0),
);

const bonus = computed(() =>
    Number(props.form.bonus || 0),
);

const upad = computed(() =>
    Number(props.form.upad || 0),
);

const paidLoan = computed(() =>
    Number(props.form.paid_loan || 0),
);

const adjustment = computed(() =>
    Number(props.form.adjustment || 0),
);

const basePay = computed(() =>
    baseSalary.value * workingDays.value,
);

const finalPayable = computed(() =>
    (
        basePay.value +
        incentive.value +
        adjustment.value -
        upad.value -
        paidLoan.value -
        deduction.value
    ).toFixed(2),
);

const hasAdjustment = computed(() =>
    adjustment.value > 0,
);

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
| Salary Summary
|--------------------------------------------------------------------------
*/

const salarySummaryItems = computed(() => [
    {
        label: 'Base salary',
        detail: `${formatCurrency(baseSalary.value)} × ${workingDays.value} days`,
        amount: basePay.value,
        type: 'positive' as const,
    },
    {
        label: 'Incentive',
        amount: incentive.value,
        type: 'positive' as const,
    },
    {
        label: 'Adjustment',
        amount: adjustment.value,
        type: 'adjustment' as const,
    },
    {
        label: 'UPAD',
        amount: -upad.value,
        type: 'deduction' as const,
    },
    {
        label: 'Paid loan',
        amount: -paidLoan.value,
        type: 'deduction' as const,
    },
    {
        label: 'Deduction',
        amount: -deduction.value,
        type: 'deduction' as const,
    },
]);

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

const {errors: frontendErrors, validate} = useZodFormValidation(
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
| Salary Preview
|--------------------------------------------------------------------------
*/

const initializeCalculatedValues = (
    data: SalaryPreview,
): void => {
    props.form.late_days = data.late_days;
    props.form.incentive = Number(data.incentive);
    props.form.deduction = Number(data.deduction);
    props.form.bonus = Number(data.bonus);
};

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

        initializeCalculatedValues(response.data);
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

watch(
    [
        () => props.form.user_id,
        () => props.form.salary_period_start,
        () => props.form.salary_period_end,
    ],
    loadPreview,
    {
        immediate: true,
    },
);
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        novalidate
        @submit.prevent="handleSubmit"
    >
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
                        :error="
                            frontendErrors.user_id ??
                            props.errors.user_id
                        "
                        :icon="User"
                        :placeholder="salaryPage.form.employeePlaceholder"
                        searchable
                    />
                </AppField>

                <AppField
                    label="Salary Month"
                    input-id="salary-month"
                    required
                >
                    <AppInput
                        id="salary-month"
                        v-model="salaryMonth"
                        :disabled="processing || isEdit"
                        :error="
                            frontendErrors.salary_period_start ??
                            props.errors.salary_period_start ??
                            frontendErrors.salary_period_end ??
                            props.errors.salary_period_end
                        "
                        :icon="CalendarDays"
                        placeholder="Select salary month"
                        type="month"
                    />
                </AppField>
            </div>

            <div
                class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t pt-4"
            >
                <div>
                    <p class="text-sm text-muted-foreground">
                        Review attendance records for this salary period.
                    </p>

                    <p
                        v-if="props.form.salary_period_start && props.form.salary_period_end"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        {{ props.form.salary_period_start }} to
                        {{ props.form.salary_period_end }}
                    </p>
                </div>

                <Button
                    :disabled="
                        !props.form.user_id ||
                        !props.form.salary_period_start ||
                        !props.form.salary_period_end
                    "
                    type="button"
                    variant="outline"
                    @click="attendanceDialogOpen = true"
                >
                    <CalendarDays class="mr-2 size-4" />
                    View Attendance
                </Button>
            </div>
        </FormSection>

        <AttendanceHistoryDialog
            v-model:open="attendanceDialogOpen"
            :end-date="props.form.salary_period_end"
            :start-date="props.form.salary_period_start"
            :user-id="props.form.user_id"
        />

        <div
            class="grid min-w-0 items-start gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(280px,1fr)]"
        >
            <div class="min-w-0 space-y-6">
                <FormSection
                    description="Review and adjust the calculated salary components before saving."
                    title="Salary Components"
                >
                    <div class="grid min-w-0 gap-5 md:grid-cols-2">
                        <AppField
                            label="Incentive"
                            input-id="salary-incentive"
                            required
                        >
                            <AppInput
                                id="salary-incentive"
                                v-model="props.form.incentive"
                                :disabled="processing"
                                :error="
                                    frontendErrors.incentive ??
                                    props.errors.incentive
                                "
                                :icon="IndianRupee"
                                min="0"
                                placeholder="Enter incentive"
                                step="0.01"
                                type="number"
                            />

                            <p class="mt-1.5 text-xs text-muted-foreground">
                                Suggested: ₹{{
                                    formatCurrency(
                                        previewData?.incentive ??
                                        props.salary?.incentive ??
                                        0,
                                    )
                                }}
                                · Rate ₹{{ formatCurrency(incentiveRate) }}/day
                            </p>
                        </AppField>

                        <AppField
                            label="Deduction"
                            input-id="salary-deduction"
                            required
                        >
                            <AppInput
                                id="salary-deduction"
                                v-model="props.form.deduction"
                                :disabled="processing"
                                :error="
                                    frontendErrors.deduction ??
                                    props.errors.deduction
                                "
                                :icon="IndianRupee"
                                min="0"
                                placeholder="Enter deduction"
                                step="0.01"
                                type="number"
                            />

                            <p class="mt-1.5 text-xs text-muted-foreground">
                                Suggested: ₹{{
                                    formatCurrency(
                                        previewData?.deduction ??
                                        props.salary?.deduction ??
                                        0,
                                    )
                                }}
                                · Rate ₹{{ formatCurrency(deductionRate) }}/day
                            </p>
                        </AppField>

                        <AppField
                            label="Bonus"
                            input-id="salary-bonus"
                            required
                        >
                            <AppInput
                                id="salary-bonus"
                                v-model="props.form.bonus"
                                :disabled="processing"
                                :error="
                                    frontendErrors.bonus ??
                                    props.errors.bonus
                                "
                                :icon="IndianRupee"
                                min="0"
                                placeholder="Enter bonus"
                                step="0.01"
                                type="number"
                            />

                            <p class="mt-1.5 text-xs text-muted-foreground">
                                Suggested: ₹{{
                                    formatCurrency(
                                        previewData?.bonus ??
                                        props.salary?.bonus ??
                                        0,
                                    )
                                }}
                                · Separate from final payable
                            </p>
                        </AppField>
                    </div>
                </FormSection>

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
                                :error="
                                    frontendErrors.upad ??
                                    props.errors.upad
                                "
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
                                :placeholder="salaryPage.form.adjustmentReasonPlaceholder"
                            />
                        </AppField>
                    </div>
                </FormSection>

                <FormSection
                    description="Final payable excludes the separate bonus amount."
                    title="Salary Summary"
                >
                    <div class="rounded-xl border bg-muted/30 p-5">
                        <div class="space-y-4">
                            <div
                                v-for="item in salarySummaryItems"
                                :key="item.label"
                                class="flex items-start justify-between gap-4"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">
                                        {{ item.label }}
                                    </p>

                                    <p
                                        v-if="'detail' in item"
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ item.detail }}
                                    </p>
                                </div>

                                <p
                                    :class="{
                                        'text-green-600 dark:text-green-400':
                                            item.type === 'positive' ||
                                            (item.type === 'adjustment' &&
                                                item.amount > 0),
                                        'text-destructive':
                                            item.type === 'deduction' ||
                                            item.amount < 0,
                                    }"
                                    class="shrink-0 text-sm font-medium tabular-nums"
                                >
                                    <template v-if="item.amount > 0">
                                        +
                                    </template>

                                    <template v-else-if="item.amount < 0">
                                        −
                                    </template>

                                    ₹{{ formatCurrency(Math.abs(item.amount)) }}
                                </p>
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium">
                                            Bonus
                                        </p>

                                        <p class="mt-0.5 text-xs text-muted-foreground">
                                            Separate amount · not included in final payable
                                        </p>
                                    </div>

                                    <p class="shrink-0 text-sm font-medium text-green-600 tabular-nums dark:text-green-400">
                                        + ₹{{ formatCurrency(bonus) }}
                                    </p>
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium">
                                            {{ salaryPage.form.finalPayableLabel }}
                                        </p>

                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ workingDays }} payable days ·
                                            {{ attendanceDays }} attendance days
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
                        </div>
                    </div>
                </FormSection>
            </div>

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
                                input-id="salary-late-days"
                                label="Late days deducted"
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
                                    :max="attendanceDays"
                                    min="0"
                                    step="1"
                                    type="number"
                                />
                            </AppField>

                            <p class="mt-2 text-xs text-muted-foreground">
                                Automatically calculated from undertime.
                                You can adjust this value before saving.
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-4 rounded-lg bg-muted/50 p-3">
                            <span class="text-sm font-medium">
                                Payable working days
                            </span>

                            <span class="text-lg font-semibold tabular-nums">
                                {{ workingDays }}
                            </span>
                        </div>

                        <div
                            v-if="showProductionEfficiency"
                            class="rounded-lg border bg-muted/30 p-4"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium">
                                        Production efficiency
                                    </p>

                                    <p class="mt-1 text-xs text-muted-foreground">
                                        Production vs effective working time
                                    </p>
                                </div>

                                <span class="text-xl font-semibold tabular-nums">
                                    {{ productionEfficiency }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 border-t pt-4">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-muted-foreground">
                                    Base salary
                                </span>

                                <span class="tabular-nums">
                                    ₹{{ formatCurrency(basePay) }}
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

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-muted-foreground">
                                    Bonus
                                </span>

                                <span class="font-medium text-green-600 tabular-nums dark:text-green-400">
                                    ₹{{ formatCurrency(bonus) }}
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
