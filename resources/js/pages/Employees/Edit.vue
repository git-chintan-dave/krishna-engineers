<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { employeePage } from '@/config/employees';
import EmployeeForm from '@/pages/Employees/components/EmployeeForm.vue';
import {
    index as employees,
    update as updateEmployee,
} from '@/routes/employees';
import type {
    Employee,
    EmployeeFormData,
} from '@/types/employee';

// Props
const props = defineProps<{
    employee: Employee;
}>();

// Layout
defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: employeePage.title,
            resourceUrl: employees().url,
        }),
    },
});

// Form
const form = useForm<EmployeeFormData>({
    name: props.employee.name,
    employee_code: props.employee.employee_code,
    phone_number: props.employee.phone_number ?? '',
    alternate_phone_number:
        props.employee.alternate_phone_number ?? '',
    email: props.employee.email ?? '',
    joining_date: props.employee.joining_date ?? '',
    password: '',
    password_confirmation: '',
    role: props.employee.role,
    shift: props.employee.shift,
    salary: Number(props.employee.salary),
    incentive_per_day: Number(
        props.employee.incentive_per_day,
    ),
    deduction_per_day: Number(
        props.employee.deduction_per_day,
    ),
    status: props.employee.status === 'active',
    aadhar_number: '',
});

// Actions
const handleSubmit = () => {
    form.transform((data) => ({
        ...data,
        status: data.status ? 'active' : 'inactive',
    })).put(
        updateEmployee(props.employee.uuid).url,
    );
};

const handleCancel = () => {
    router.visit(employees().url);
};
</script>

<template>
    <FormLayout
        :title="employeePage.form.editTitle"
        :description="
            employeePage.form.editDescription
        "
    >
        <EmployeeForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                employeePage.form.editSubmitLabel
            "
            :is-edit="true"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
