<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Printer } from '@lucide/vue';
import { computed, ref } from 'vue';

import AttendanceHistoryDialog from '@/components/attendance/AttendanceHistoryDialog.vue';
import { Button } from '@/components/ui/button';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { salaryPage } from '@/config/salaries';
import { index as salaries } from '@/routes/salaries';
import type { Salary } from '@/types/salary';

const props = defineProps<{
    salary: Salary;
    show_production_efficiency: boolean;
    production_efficiency: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('details', {
            resource: salaryPage.title,
            resourceUrl: salaries().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Company Information
|--------------------------------------------------------------------------
*/

const companyName = 'Krishna Engineers';

const companyAddress =
    '437/49 Amar Estate, B/H Asarva Chemical, Nr. Bhikshukh Gruh, Odhav GIDC, Odhav, Ahmedababd, 382415.';

/*
|--------------------------------------------------------------------------
| Attendance Dialog
|--------------------------------------------------------------------------
*/

const attendanceDialogOpen = ref(false);

const totalUndertimeSeconds = computed(() =>
    Math.max(0, Number(props.salary.total_undertime_seconds ?? 0)),
);

const calculatedLateDays = computed(() =>
    Math.floor(totalUndertimeSeconds.value / (12 * 60 * 60)),
);

const formatDuration = (seconds: number): string => {
    const totalMinutes = Math.floor(Math.max(0, seconds) / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    return `${hours}h ${String(minutes).padStart(2, '0')}m`;
};

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const printSalarySlip = (): void => {
    window.print();
};

const goBack = (): void => {
    router.visit(salaries().url);
};
</script>

<template>
    <div class="min-h-full bg-muted/30 print:min-h-0 print:bg-white">
        <div
            class="mx-auto w-full max-w-4xl p-4 sm:p-6 lg:p-8 print:max-w-none print:p-0"
        >
            <!-- Page Actions -->
            <div
                class="mb-4 flex items-center justify-between gap-3 print:hidden"
            >
                <Button
                    class="cursor-pointer"
                    variant="outline"
                    @click="goBack"
                >
                    <ArrowLeft class="mr-2 size-4" />
                    Back
                </Button>

                <Button class="cursor-pointer" @click="printSalarySlip">
                    <Printer class="mr-2 size-4" />
                    Print Salary Slip
                </Button>
            </div>

            <!-- Salary Slip -->
            <div
                id="salary-slip"
                class="overflow-hidden rounded-xl border bg-background shadow-sm print:rounded-none print:border-0 print:shadow-none"
            >
                <!-- Header -->
                <div class="border-b px-6 py-6 sm:px-8 print:px-5 print:py-4">
                    <div class="flex items-center justify-between gap-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <img
                                src="/logo_new.svg"
                                alt="Krishna Engineers Logo"
                                class="h-14 w-auto shrink-0 object-contain print:h-12"
                            />

                            <div class="min-w-0">
                                <h1
                                    class="text-lg font-bold tracking-wide uppercase sm:text-xl print:text-base"
                                >
                                    {{ companyName }}
                                </h1>

                                <p
                                    class="mt-1 max-w-md text-xs leading-relaxed text-muted-foreground print:text-[10px]"
                                >
                                    {{ companyAddress }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 text-right">
                            <p
                                class="text-xs font-semibold tracking-[0.16em] text-muted-foreground uppercase print:text-[10px]"
                            >
                                Salary Slip
                            </p>

                            <p
                                class="mt-2 text-sm font-medium print:mt-1 print:text-xs"
                            >
                                {{ salary.salary_period_start_formatted }}
                                <span class="px-1 text-muted-foreground"
                                    >–</span
                                >
                                {{ salary.salary_period_end_formatted }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Employee Summary -->
                <div class="border-b px-6 py-5 sm:px-8 print:px-5 print:py-3">
                    <div
                        class="grid items-center"
                        :class="
                            show_production_efficiency
                                ? 'grid-cols-4'
                                : 'grid-cols-3'
                        "
                    >
                        <div class="min-w-0 text-center">
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase print:text-[10px]"
                            >
                                Employee
                            </p>

                            <p
                                class="mt-1 truncate text-sm font-semibold print:mt-0.5 print:text-xs"
                            >
                                {{ salary.user.name }}
                            </p>
                        </div>

                        <div class="min-w-0 text-center">
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase print:text-[10px]"
                            >
                                Employee Code
                            </p>

                            <p
                                class="mt-1 text-sm font-semibold print:mt-0.5 print:text-xs"
                            >
                                {{ salary.user.employee_code }}
                            </p>
                        </div>

                        <div class="min-w-0 text-center">
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase print:text-[10px]"
                            >
                                Payable Working Days
                            </p>

                            <p
                                class="mt-1 text-sm font-semibold print:mt-0.5 print:text-xs"
                            >
                                {{ salary.working_days }}
                            </p>
                        </div>

                        <div
                            class="min-w-0 text-center"
                            v-if="show_production_efficiency"
                        >
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase print:text-[10px]"
                            >
                                Production efficiency
                            </p>

                            <p
                                class="mt-1 text-sm font-semibold print:mt-0.5 print:text-xs"
                            >
                                {{ production_efficiency }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Salary Details -->
                <div class="px-6 py-6 sm:px-8 print:px-5 print:py-4">
                    <!-- Attendance Summary -->
                    <section class="mb-7 print:mb-4">
                        <div
                            class="mb-3 flex flex-wrap items-center justify-between gap-3 print:mb-2"
                        >
                            <h2
                                class="text-sm font-semibold tracking-wide uppercase print:text-[11px]"
                            >
                                Attendance Summary
                            </h2>

                            <Button
                                class="print:hidden"
                                type="button"
                                variant="outline"
                                @click="attendanceDialogOpen = true"
                            >
                                <CalendarDays class="mr-2 size-4" />
                                View Attendance
                            </Button>
                        </div>

                        <div
                            class="overflow-hidden rounded-lg border print:rounded-md"
                        >
                            <div
                                class="grid grid-cols-[1fr_auto] border-b bg-muted/30 px-4 py-2.5 text-xs font-medium tracking-wide text-muted-foreground uppercase print:px-3 print:py-1.5 print:text-[9px]"
                            >
                                <span>Component</span>
                                <span class="text-right">Value</span>
                            </div>

                            <div class="divide-y">
                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Attendance Days</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ salary.attendance_days }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Total Undertime</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{
                                            formatDuration(
                                                totalUndertimeSeconds,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Calculated Late Days</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ calculatedLateDays }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Late Days Deducted</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ salary.late_days }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 bg-muted/30 px-4 py-3 text-sm font-semibold print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Payable Working Days</span>
                                    <span class="text-right tabular-nums">
                                        {{ salary.working_days }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Earnings -->
                    <section>
                        <h2
                            class="mb-3 text-sm font-semibold tracking-wide uppercase print:mb-2 print:text-[11px]"
                        >
                            Earnings
                        </h2>

                        <div
                            class="overflow-hidden rounded-lg border print:rounded-md"
                        >
                            <div
                                class="grid grid-cols-[1fr_auto] border-b bg-muted/30 px-4 py-2.5 text-xs font-medium tracking-wide text-muted-foreground uppercase print:px-3 print:py-1.5 print:text-[9px]"
                            >
                                <span>Component</span>
                                <span class="text-right">Amount</span>
                            </div>

                            <div class="divide-y">
                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Base Salary</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ salary.base_salary_formatted }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Incentive</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ salary.incentive_formatted }}
                                    </span>
                                </div>

                                <div
                                    v-if="Number(salary.adjustment) !== 0"
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Adjustment</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ salary.adjustment_formatted }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Deductions -->
                    <section class="mt-7 print:mt-4">
                        <h2
                            class="mb-3 text-sm font-semibold tracking-wide uppercase print:mb-2 print:text-[11px]"
                        >
                            Deductions
                        </h2>

                        <div
                            class="overflow-hidden rounded-lg border print:rounded-md"
                        >
                            <div
                                class="grid grid-cols-[1fr_auto] border-b bg-muted/30 px-4 py-2.5 text-xs font-medium tracking-wide text-muted-foreground uppercase print:px-3 print:py-1.5 print:text-[9px]"
                            >
                                <span>Component</span>
                                <span class="text-right">Amount</span>
                            </div>

                            <div class="divide-y">
                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>UPAD</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ salary.upad_formatted }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Paid Loan</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ salary.paid_loan_formatted }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>Deduction</span>
                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ salary.deduction_formatted }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Adjustment Reason -->
                    <div
                        v-if="salary.adjustment_reason"
                        class="mt-6 rounded-lg border px-4 py-3 print:mt-3 print:px-3 print:py-2"
                    >
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase print:text-[9px]"
                        >
                            Adjustment Reason
                        </p>

                        <p
                            class="mt-1 text-sm leading-5 whitespace-pre-line print:mt-0.5 print:text-xs print:leading-4"
                        >
                            {{ salary.adjustment_reason }}
                        </p>
                    </div>

                    <!-- Net Payable -->
                    <div
                        class="mt-7 rounded-lg border bg-muted/30 px-5 py-5 print:mt-4 print:px-4 print:py-3"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase print:text-[10px]"
                                >
                                    Net Payable
                                </p>

                                <p
                                    class="mt-1 text-sm text-muted-foreground print:mt-0.5 print:text-[10px]"
                                >
                                    Final salary amount
                                </p>
                            </div>

                            <p
                                class="shrink-0 text-xl font-bold tabular-nums sm:text-2xl print:text-lg"
                            >
                                {{ salary.final_payable_formatted }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="border-t px-6 py-5 sm:px-8 print:px-5 print:py-3">
                    <div
                        class="flex items-center justify-between gap-4 text-sm print:text-xs"
                    >
                        <div>
                            <span class="text-muted-foreground">Status:</span>
                            <span class="ml-1 font-medium">
                                {{ salary.status_label }}
                            </span>
                        </div>

                        <div v-if="salary.paid_at_formatted">
                            <span class="text-muted-foreground">Paid On:</span>
                            <span class="ml-1 font-medium">
                                {{ salary.paid_at_formatted }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Print Signatures -->
                <div
                    class="hidden border-t px-6 pt-12 pb-8 sm:px-8 print:grid print:grid-cols-2 print:gap-24"
                >
                    <div
                        class="border-t pt-2 text-center text-xs text-muted-foreground"
                    >
                        Employee Signature
                    </div>

                    <div
                        class="border-t pt-2 text-center text-xs text-muted-foreground"
                    >
                        Authorized Signature
                    </div>
                </div>
            </div>
        </div>

        <!-- Kept outside the printable salary slip -->
        <AttendanceHistoryDialog
            v-model:open="attendanceDialogOpen"
            :user-id="salary.user_id"
            :start-date="salary.salary_period_start"
            :end-date="salary.salary_period_end"
        />
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    header,
    nav,
    aside,
    [role='banner'],
    [role='navigation'],
    [aria-label='Breadcrumb'],
    [aria-label='breadcrumbs'],
    .no-print,
    .print\:hidden {
        display: none !important;
    }

    html,
    body {
        width: 100% !important;
        min-width: 0 !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        background: white !important;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    #app {
        width: 100% !important;
        min-width: 0 !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    #salary-slip {
        display: block !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: white !important;
    }

    #salary-slip * {
        overflow: visible !important;
    }

    #salary-slip > div,
    #salary-slip section {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
