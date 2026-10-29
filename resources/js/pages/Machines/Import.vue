<script lang="ts" setup>
import {Head} from '@inertiajs/vue3';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ImportFile from '@/components/common/ImportFile.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';

import {machinePage} from '@/config/machines';

import {index as machines} from '@/routes/machines';
import {store as submitImport} from '@/routes/machines/import';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: machinePage.title,
            resourceUrl: machines().url,
        }),
    },
});
</script>

<template>
    <Head title="Import Machines"/>

    <div class="space-y-6 p-6">
        <AppPageHeader
            description="Upload a machine spreadsheet to create new machines or update existing records."
            title="Import Machines"
        />

        <ImportFile
            :index-url="machines().url"
            :information-items="[
                'Name is required.',
                'Number is optional and identifies an existing machine when provided.',
                'Existing machines are matched using their machine number.',
                'Ensure machine names and numbers are valid.',
            ]"
            :submit-url="submitImport().url"
            accepted-files=".xlsx,.xls,.csv"
            description="Upload the machine spreadsheet to import machine records."
            sample-file="/samples/machines.xlsx"
            sample-file-name="machines.xlsx"
            submit-button-text="Import Machines"
            title="Import Machines"
            upload-description="Drag and drop your machine spreadsheet here, or click to browse."
            upload-text="Upload machine spreadsheet"
        />
    </div>
</template>
