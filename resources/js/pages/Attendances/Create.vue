<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { attendancePage } from '@/config/attendances';
import AttendanceForm from '@/pages/Attendances/components/AttendanceForm.vue';
import { index as attendances, store } from '@/routes/attendances';
import type {
    AttendanceEmployeeOption,
    AttendanceFormData,
} from '@/types/attendance';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

defineProps<{
    employees: AttendanceEmployeeOption[];
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: attendancePage.title,
            resourceUrl: attendances().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<AttendanceFormData>({
    user_id: '',
    attendance_date: '',
    start_at: '',
    end_at: null,
    breaks: [],
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
    router.visit(attendances().url);
};
</script>

<template>
    <FormLayout
        :title="attendancePage.form.createTitle"
        :description="attendancePage.form.createDescription"
    >
        <AttendanceForm
            :form="form"
            :employees="employees"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="attendancePage.form.createSubmitLabel"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
