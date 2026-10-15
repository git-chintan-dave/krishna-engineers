<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { attendancePage } from '@/config/attendances';
import AttendanceForm from '@/pages/Attendances/components/AttendanceForm.vue';
import {
    index as attendances,
    update as updateAttendance,
} from '@/routes/attendances';
import type {
    Attendance,
    AttendanceEmployeeOption,
    AttendanceFormData,
} from '@/types/attendance';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    attendance: Attendance;
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: attendancePage.title,
            resourceUrl: attendances().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Employee Options
|--------------------------------------------------------------------------
*/

const employees: AttendanceEmployeeOption[] = [
    {
        value: String(props.attendance.user.id),
        label: `${props.attendance.user.employee_code} - ${props.attendance.user.name}`,
    },
];

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<AttendanceFormData>({
    user_id: props.attendance.user_id,
    attendance_date: props.attendance.attendance_date,
    start_at: props.attendance.start_at,
    end_at: props.attendance.end_at,
    breaks: props.attendance.breaks.map(
        (attendanceBreak) => ({
            start_at: attendanceBreak.start_at,
            end_at: attendanceBreak.end_at ?? '',
        }),
    ),
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.put(
        updateAttendance(props.attendance.uuid).url,
    );
};

const handleCancel = () => {
    router.visit(attendances().url);
};
</script>

<template>
    <FormLayout
        :title="attendancePage.form.editTitle"
        :description="attendancePage.form.editDescription"
    >
        <AttendanceForm
            :form="form"
            :attendance="attendance"
            :employees="employees"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="attendancePage.form.editSubmitLabel"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
