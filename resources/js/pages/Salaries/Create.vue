<script lang="ts" setup>
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { salaryPage } from '@/config/salaries';
import SalaryForm from '@/pages/Salaries/components/SalaryForm.vue';
import { index as salaries, store } from '@/routes/salaries';
import type { SalaryFormData } from '@/types/salary';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: salaryPage.title,
            resourceUrl: salaries().url,
        }),
    },
});

const form = useForm<SalaryFormData>({
    user_id: '',
    salary_period_start: '',
    salary_period_end: '',
    upad: 0,
    paid_loan: 0,
    adjustment: 0,
    adjustment_reason: '',
});

const handleSubmit = (): void => {
    form.post(store().url);
};

const handleCancel = (): void => {
    router.visit(salaries().url);
};
</script>

<template>
    <FormLayout
        :description="salaryPage.form.createDescription"
        :title="salaryPage.form.createTitle"
    >
        <SalaryForm
            :errors="form.errors"
            :form="form"
            :processing="form.processing"
            :submit-label="salaryPage.form.createSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
