<script lang="ts" setup>
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { employeePage } from '@/config/employees';
import EmployeeForm from '@/pages/Employees/components/EmployeeForm.vue';
import { index as employees, store } from '@/routes/employees';
import type { EmployeeFormData } from '@/types/employee';

// Layout
defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: employeePage.title,
            resourceUrl: employees().url,
        }),
    },
});

// Form
const form = useForm<EmployeeFormData>({
    name: '',
    employee_code: '',
    phone_number: '',
    alternate_phone_number: '',
    email: '',
    joining_date: '',
    password: '',
    password_confirmation: '',
    role: null,
    shift: null,
    salary: null,
    incentive_per_day: null,
    deduction_per_day: null,
    status: true,
    aadhar_number: '',
});

// Actions
const handleSubmit = () => {
    form.transform((data) => ({
        ...data,
        status: data.status ? 'active' : 'inactive',
    })).post(store().url);
};

const handleCancel = () => {
    router.visit(employees().url);
};
</script>

<template>
    <FormLayout
        :description="employeePage.form.createDescription"
        :title="employeePage.form.createTitle"
    >
        <EmployeeForm
            :errors="form.errors"
            :form="form"
            :processing="form.processing"
            :submit-label="employeePage.form.createSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
