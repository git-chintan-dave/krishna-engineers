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
import {salaryPage} from '@/config/salaries';
import {preview as previewSalary} from '@/routes/salaries';
import type {Salary, SalaryFormData} from '@/types/salary';

type SalaryPreview = {
    base_salary: string;
    working_days: number;
    incentive_rate: string;
    incentive: string;
    deduction_rate: string;
    deduction: string;
    final_payable: string;
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<SalaryFormData>;
        salary?: Salary;
        errors?: Partial<Record<keyof SalaryFormData, string>>;
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

const previewData = ref<SalaryPreview | null>(null);
const previewLoading = ref(false);

let previewRequestId = 0;

const isEdit = computed(() => Boolean(props.salary));

const baseSalary = computed(() =>
    Number(props.salary?.base_salary ?? previewData.value?.base_salary ?? 0),
);

const workingDays = computed(
    () => props.salary?.working_days ?? previewData.value?.working_days ?? 0,
);

const incentive = computed(() =>
    Number(props.salary?.incentive ?? previewData.value?.incentive ?? 0),
);

const deduction = computed(() =>
    Number(props.salary?.deduction ?? previewData.value?.deduction ?? 0),
);

const upad = computed(() => Number(props.form.upad || 0));

const paidLoan = computed(() => Number(props.form.paid_loan || 0));

const adjustment = computed(() => Number(props.form.adjustment || 0));

const finalPayable = computed(() =>
    (
        baseSalary.value +
        incentive.value +
        adjustment.value -
        upad.value -
        paidLoan.value -
        deduction.value
    ).toFixed(2),
);

const hasAdjustment = computed(() => adjustment.value !== 0);

const loadPreview = async (): Promise<void> => {
    if (
        isEdit.value ||
        !props.form.user_id ||
        !props.form.salary_period_start ||
        !props.form.salary_period_end
    ) {
        previewData.value = null;
        previewLoading.value = false;

        return;
    }

    const requestId = ++previewRequestId;

    previewLoading.value = true;

    try {
        const response = await axios.get(previewSalary().url, {
            params: {
                user_id: props.form.user_id,
                salary_period_start: props.form.salary_period_start,
                salary_period_end: props.form.salary_period_end,
            },
            headers: {
                Accept: 'application/json',
            },
        });

        if (requestId !== previewRequestId) {
            return;
        }

        previewData.value = response.data as SalaryPreview;
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

const setPeriodEndFromStart = (): void => {
    if (isEdit.value || !props.form.salary_period_start) {
        return;
    }

    const startDate = new Date(`${props.form.salary_period_start}T00:00:00`);

    if (Number.isNaN(startDate.getTime())) {
        return;
    }

    const endDate = new Date(
        startDate.getFullYear(),
        startDate.getMonth() + 1,
        0,
    );

    const year = endDate.getFullYear();
    const month = String(endDate.getMonth() + 1).padStart(2, '0');
    const day = String(endDate.getDate()).padStart(2, '0');

    props.form.salary_period_end = `${year}-${month}-${day}`;
};

watch(() => props.form.salary_period_start, setPeriodEndFromStart);

watch(
    [
        () => props.form.user_id,
        () => props.form.salary_period_start,
        () => props.form.salary_period_end,
    ],
    loadPreview,
);

const formatAmount = (amount: number): string => {
    return `₹${amount.toFixed(2)}`;
};
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="emit('submit')">
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
                        :error="props.errors.user_id"
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
                        :error="props.errors.salary_period_start"
                        :icon="CalendarDays"
                        :placeholder="
                            salaryPage.form.salaryPeriodStartPlaceholder
                        "
                        required
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
                        :error="props.errors.salary_period_end"
                        :icon="CalendarDays"
                        :placeholder="
                            salaryPage.form.salaryPeriodEndPlaceholder
                        "
                        required
                        type="date"
                    />
                </AppField>

                <AppField
                    :label="salaryPage.form.baseSalaryLabel"
                    input-id="salary-base-salary"
                >
                    <AppInput
                        id="salary-base-salary"
                        :icon="IndianRupee"
                        :model-value="formatAmount(baseSalary)"
                        disabled
                    />
                </AppField>

                <AppField
                    :label="salaryPage.form.workingDaysLabel"
                    input-id="salary-working-days"
                >
                    <AppInput
                        id="salary-working-days"
                        :model-value="String(workingDays)"
                        disabled
                    />
                </AppField>

                <AppField
                    :label="salaryPage.form.incentiveLabel"
                    input-id="salary-incentive"
                >
                    <AppInput
                        id="salary-incentive"
                        :icon="IndianRupee"
                        :model-value="formatAmount(incentive)"
                        disabled
                    />
                </AppField>

                <AppField
                    :label="salaryPage.form.deductionLabel"
                    input-id="salary-deduction"
                >
                    <AppInput
                        id="salary-deduction"
                        :icon="IndianRupee"
                        :model-value="formatAmount(deduction)"
                        disabled
                    />
                </AppField>

                <div
                    v-if="previewLoading"
                    class="flex items-center gap-2 text-sm text-muted-foreground md:col-span-2"
                >
                    <IndianRupee
                        aria-hidden="true"
                        class="size-4 animate-pulse"
                    />

                    <span> Loading salary details... </span>
                </div>
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
                        :error="props.errors.upad"
                        :icon="IndianRupee"
                        :placeholder="salaryPage.form.upadPlaceholder"
                        min="0"
                        required
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
                        :error="props.errors.paid_loan"
                        :icon="IndianRupee"
                        :placeholder="salaryPage.form.paidLoanPlaceholder"
                        min="0"
                        required
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
                        :error="props.errors.adjustment"
                        :icon="IndianRupee"
                        :placeholder="salaryPage.form.adjustmentPlaceholder"
                        required
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
                        :error="props.errors.adjustment_reason"
                        :placeholder="
                            salaryPage.form.adjustmentReasonPlaceholder
                        "
                    />
                </AppField>
            </div>
        </FormSection>

        <FormSection
            description="Review the final payable amount before saving."
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
                            Base salary + incentive + adjustment − upad − paid
                            loan − deduction.
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <IndianRupee
                            aria-hidden="true"
                            class="size-5 text-muted-foreground"
                        />

                        <p class="text-2xl font-semibold tabular-nums">
                            {{ Number(finalPayable).toFixed(2) }}
                        </p>
                    </div>
                </div>
            </div>
        </FormSection>

        <FormActions
            :cancel-label="salaryPage.form.cancelLabel"
            :processing="processing || previewLoading"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
