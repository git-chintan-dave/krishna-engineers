<script lang="ts" setup>
import {Head} from '@inertiajs/vue3';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ImportFile from '@/components/common/ImportFile.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {partSetupPage} from '@/config/partSetups';
import {index as partSetups} from '@/routes/part-setups';
import {store as submitImport} from '@/routes/part-setups/import';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: partSetupPage.title,
            resourceUrl: partSetups().url,
        }),
    },
});
</script>

<template>
    <Head title="Import Part Setups" />

    <div class="space-y-6 p-6">
        <AppPageHeader
            description="Upload a part setup spreadsheet to create or update part setup records."
            title="Import Part Setups"
        />

        <ImportFile
            accepted-files=".xlsx,.xls,.csv"
            description="Upload the part setup spreadsheet to import part and setup combinations."
            :index-url="partSetups().url"
            :information-items="[
                'Part, Setup, Machine Cycle Time, and Actual Cycle Time are required.',
                'Part and Setup names are matched case-insensitively against existing records.',
                'Both the Part and Setup must already exist in the system.',
                'Part + Setup identifies an existing part setup.',
                'Cycle times must be non-negative integers in seconds.',
                'Existing images and UUIDs are preserved when updating records.',
            ]"
            sample-file="/samples/part-setups.xlsx"
            sample-file-name="part-setups.xlsx"
            submit-button-text="Import Part Setups"
            :submit-url="submitImport().url"
            title="Import Part Setups"
            upload-description="Drag and drop your part setup spreadsheet here, or click to browse."
            upload-text="Upload part setup spreadsheet"
        />
    </div>
</template>
