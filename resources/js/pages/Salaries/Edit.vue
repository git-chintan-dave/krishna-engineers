<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { salaryPage } from '@/config/salaries';
import SalaryForm from '@/pages/Salaries/components/SalaryForm.vue';
import {
    index as salaries,
    update,
} from '@/routes/salaries';
import type {
    Salary,
    SalaryEmployeeOption,
    SalaryFormData,
} from '@/types/salary';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    salary: Salary;
    employees: SalaryEmployeeOption[];
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: salaryPage.title,
            resourceUrl: salaries().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<SalaryFormData>({
    user_id: props.salary.user_id,
    salary_period_start: props.salary.salary_period_start,
    salary_period_end: props.salary.salary_period_end,
    upad: props.salary.upad,
    paid_loan: props.salary.paid_loan,
    adjustment: props.salary.adjustment,
    adjustment_reason:
        props.salary.adjustment_reason ?? '',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.put(update(props.salary.id).url);
};

const handleCancel = () => {
    router.visit(salaries().url);
};
</script>

<template>
    <FormLayout
        :title="salaryPage.form.editTitle"
        :description="
            salaryPage.form.editDescription
        "
    >
        <SalaryForm
            :form="form"
            :salary="salary"
            :employees="employees"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                salaryPage.form.editSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
