<script lang="ts" setup>
import { Head } from '@inertiajs/vue3';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ImportFile from '@/components/common/ImportFile.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { setupPage } from '@/config/setups';
import { index as setups } from '@/routes/setups';
import { store as submitImport } from '@/routes/setups/import';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: setupPage.title,
            resourceUrl: setups().url,
        }),
    },
});
</script>

<template>
    <Head title="Import Setups" />

    <div class="space-y-6 p-6">
        <AppPageHeader
            description="Upload a setup spreadsheet to create or update setup records."
            title="Import Setups"
        />

        <ImportFile
            accepted-files=".xlsx,.xls,.csv"
            description="Upload the setup spreadsheet to import setup records."
            :information-items="[
                'Name is required.',
                'Setup names are used to identify existing records.',
                'Duplicate setup names within the uploaded file are not allowed.',
            ]"
            sample-file="/samples/setups.xlsx"
            sample-file-name="setups.xlsx"
            submit-button-text="Import Setups"
            :submit-url="submitImport().url"
            title="Import Setups"
            upload-description="Drag and drop your setup spreadsheet here, or click to browse."
            upload-text="Upload setup spreadsheet"
        />
    </div>
</template>
