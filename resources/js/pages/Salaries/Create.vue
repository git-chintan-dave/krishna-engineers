<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { salaryPage } from '@/config/salaries';
import SalaryForm from '@/pages/Salaries/components/SalaryForm.vue';
import {
    index as salaries,
    store,
} from '@/routes/salaries';
import type {
    SalaryEmployeeOption,
    SalaryFormData,
} from '@/types/salary';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

defineProps<{
    employees: SalaryEmployeeOption[];
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
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
    user_id: '',
    salary_period_start: '',
    salary_period_end: '',
    upad: 0,
    paid_loan: 0,
    adjustment: 0,
    adjustment_reason: '',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.post(store().url);
};

const handleCancel = () => {
    router.visit(salaries().url);
};
</script>

<template>
    <FormLayout
        :title="salaryPage.form.createTitle"
        :description="
            salaryPage.form.createDescription
        "
    >
        <SalaryForm
            :form="form"
            :employees="employees"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                salaryPage.form.createSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
