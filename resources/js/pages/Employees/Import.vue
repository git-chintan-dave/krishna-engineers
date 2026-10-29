<script lang="ts" setup>
import {Head} from '@inertiajs/vue3';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ImportFile from '@/components/common/ImportFile.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {employeePage} from '@/config/employees';
import {index as employees} from '@/routes/employees';
import {store as submitImport} from '@/routes/employees/import';

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: employeePage.title,
            resourceUrl: employees().url,
        }),
    },
});
</script>

<template>
    <Head title="Import Employees"/>

    <div class="space-y-6 p-6">
        <AppPageHeader
            description="Upload an employee spreadsheet to create new employees or update existing records."
            title="Import Employees"
        />

        <ImportFile
            :index-url="employees().url"
            :information-items="[
                'Name, Employee Code, and Role are required.',
                'Employee Code is used to identify existing employees.',
                'Existing employees will be updated when their employee code matches.',
                'Role and Shift values must match the available system options.',
                'Ensure phone numbers, dates, salary, incentive, and deduction values are valid.',
            ]"
            :submit-url="submitImport().url"
            accepted-files=".xlsx,.xls,.csv"
            description="Upload the employee spreadsheet to import employee records."
            sample-file="/samples/employees.xlsx"
            sample-file-name="employees.xlsx"
            submit-button-text="Import Employees"
            title="Import Employees"
            upload-description="Drag and drop your employee spreadsheet here, or click to browse."
            upload-text="Upload employee spreadsheet"
        />
    </div>
</template>
