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
    SalaryFormData,
} from '@/types/salary';

const props = defineProps<{
    salary: Salary;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: salaryPage.title,
            resourceUrl: salaries().url,
        }),
    },
});

const form = useForm<SalaryFormData>({
    user_id: props.salary.user_id,
    salary_period_start:
    props.salary.salary_period_start,
    salary_period_end:
    props.salary.salary_period_end,
    upad: props.salary.upad,
    paid_loan: props.salary.paid_loan,
    adjustment: props.salary.adjustment,
    adjustment_reason:
        props.salary.adjustment_reason ?? '',
});

const handleSubmit = (): void => {
    form.put(update(props.salary.uuid).url);
};

const handleCancel = (): void => {
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
