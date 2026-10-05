<script setup lang="ts">
import {ArrowLeft, Printer} from '@lucide/vue';
import {router} from '@inertiajs/vue3';

import {Button} from '@/components/ui/button';
import {index as salaries} from '@/routes/salaries';
import type {Salary} from '@/types/salary';
import {useCrudBreadcrumbs} from "@/composables/useCrudBreadcrumbs";
import {salaryPage} from "@/config/salaries";

const props = defineProps<{
    salary: Salary;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('details', {
            resource: salaryPage.title,
            resourceUrl: salaries().url,
        }),
    },
});

const companyName = 'Krishna Engineers';
const companyAddress = '437/49 Amar Estate, B/H Asarva Chemical, Nr. Bhikshukh Gruh, Odhav GIDC, Odhav, Ahmedababd, 382415.';

const printSalarySlip = () => {
    window.print();
};

const goBack = () => {
    router.visit(salaries().url);
};
</script>

<template>
    <div
        class="min-h-full bg-muted/30 print:min-h-0 print:bg-white"
    >
        <div
            class="mx-auto w-full max-w-4xl p-4 sm:p-6 lg:p-8 print:max-w-none print:p-0"
        >
            <!-- Page Actions -->
            <div
                class="mb-4 flex items-center justify-between gap-3 print:hidden"
            >
                <Button
                    variant="outline"
                    class="cursor-pointer"
                    @click="goBack"
                >
                    <ArrowLeft class="mr-2 size-4" />
                    Back
                </Button>

                <Button
                    class="cursor-pointer"
                    @click="printSalarySlip"
                >
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
                <div
                    class="border-b px-6 py-6 sm:px-8 print:px-5 print:py-4"
                >
                    <div
                        class="flex items-start justify-between gap-6"
                    >
                        <div class="min-w-0">
                            <h1
                                class="text-2xl font-bold tracking-tight print:text-xl"
                            >
                                {{ companyName }}
                            </h1>

                            <p
                                class="mt-1 whitespace-pre-line text-sm leading-5 text-muted-foreground print:text-xs print:leading-4"
                            >
                                {{ companyAddress }}
                            </p>
                        </div>

                        <div
                            class="shrink-0 text-right"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground print:text-[10px]"
                            >
                                Salary Slip
                            </p>

                            <p
                                class="mt-2 text-sm font-medium print:mt-1 print:text-xs"
                            >
                                {{
                                    salary.salary_period_start_formatted
                                }}
                                <span class="px-1 text-muted-foreground">
                                    –
                                </span>
                                {{
                                    salary.salary_period_end_formatted
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Employee Summary -->
                <div
                    class="border-b px-6 py-5 sm:px-8 print:px-5 print:py-3"
                >
                    <div
                        class="grid grid-cols-3 items-center"
                    >
                        <!-- Left -->
                        <div class="min-w-0 text-left">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-muted-foreground print:text-[10px]"
                            >
                                Employee
                            </p>

                            <p
                                class="mt-1 truncate text-sm font-semibold print:mt-0.5 print:text-xs"
                            >
                                {{ salary.user.name }}
                            </p>
                        </div>

                        <!-- Center -->
                        <div class="min-w-0 text-center">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-muted-foreground print:text-[10px]"
                            >
                                Employee Code
                            </p>

                            <p
                                class="mt-1 text-sm font-semibold print:mt-0.5 print:text-xs"
                            >
                                {{ salary.user.employee_code }}
                            </p>
                        </div>

                        <!-- Right -->
                        <div class="min-w-0 text-right">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-muted-foreground print:text-[10px]"
                            >
                                Working Days
                            </p>

                            <p
                                class="mt-1 text-sm font-semibold print:mt-0.5 print:text-xs"
                            >
                                {{ salary.working_days }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Salary Details -->
                <div
                    class="px-6 py-6 sm:px-8 print:px-5 print:py-4"
                >
                    <!-- Earnings -->
                    <section>
                        <h2
                            class="mb-3 text-sm font-semibold uppercase tracking-wide print:mb-2 print:text-[11px]"
                        >
                            Earnings
                        </h2>

                        <div
                            class="overflow-hidden rounded-lg border print:rounded-md"
                        >
                            <div
                                class="grid grid-cols-[1fr_auto] border-b bg-muted/30 px-4 py-2.5 text-xs font-medium uppercase tracking-wide text-muted-foreground print:px-3 print:py-1.5 print:text-[9px]"
                            >
                                <span>
                                    Component
                                </span>

                                <span class="text-right">
                                    Amount
                                </span>
                            </div>

                            <div class="divide-y">
                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>
                                        Base Salary
                                    </span>

                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{
                                            salary.base_salary_formatted
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>
                                        Incentive
                                    </span>

                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{
                                            salary.incentive_formatted
                                        }}
                                    </span>
                                </div>

                                <div
                                    v-if="
                                        Number(
                                            salary.adjustment,
                                        ) !== 0
                                    "
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>
                                        Adjustment
                                    </span>

                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{
                                            salary.adjustment_formatted
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Deductions -->
                    <section
                        class="mt-7 print:mt-4"
                    >
                        <h2
                            class="mb-3 text-sm font-semibold uppercase tracking-wide print:mb-2 print:text-[11px]"
                        >
                            Deductions
                        </h2>

                        <div
                            class="overflow-hidden rounded-lg border print:rounded-md"
                        >
                            <div
                                class="grid grid-cols-[1fr_auto] border-b bg-muted/30 px-4 py-2.5 text-xs font-medium uppercase tracking-wide text-muted-foreground print:px-3 print:py-1.5 print:text-[9px]"
                            >
                                <span>
                                    Component
                                </span>

                                <span class="text-right">
                                    Amount
                                </span>
                            </div>

                            <div class="divide-y">
                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>
                                        UPAD
                                    </span>

                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{
                                            salary.upad_formatted
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>
                                        Paid Loan
                                    </span>

                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{
                                            salary.paid_loan_formatted
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm print:px-3 print:py-2 print:text-xs"
                                >
                                    <span>
                                        Deduction
                                    </span>

                                    <span
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{
                                            salary.deduction_formatted
                                        }}
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
                            class="text-xs font-medium uppercase tracking-wide text-muted-foreground print:text-[9px]"
                        >
                            Adjustment Reason
                        </p>

                        <p
                            class="mt-1 whitespace-pre-line text-sm leading-5 print:mt-0.5 print:text-xs print:leading-4"
                        >
                            {{ salary.adjustment_reason }}
                        </p>
                    </div>

                    <!-- Net Payable -->
                    <div
                        class="mt-7 rounded-lg border bg-muted/30 px-5 py-5 print:mt-4 print:px-4 print:py-3"
                    >
                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-muted-foreground print:text-[10px]"
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
                                {{
                                    salary.final_payable_formatted
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div
                    class="border-t px-6 py-5 sm:px-8 print:px-5 print:py-3"
                >
                    <div
                        class="flex items-center justify-between gap-4 text-sm print:text-xs"
                    >
                        <div>
                            <span
                                class="text-muted-foreground"
                            >
                                Status:
                            </span>

                            <span
                                class="ml-1 font-medium"
                            >
                                {{ salary.status_label }}
                            </span>
                        </div>

                        <div
                            v-if="salary.paid_at_formatted"
                        >
                            <span
                                class="text-muted-foreground"
                            >
                                Paid On:
                            </span>

                            <span
                                class="ml-1 font-medium"
                            >
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
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 8mm;
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
    }

    #salary-slip * {
        overflow: visible !important;
    }

    .print\:hidden {
        display: none !important;
    }

    #salary-slip > div {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    #salary-slip section {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
