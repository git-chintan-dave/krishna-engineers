<script lang="ts" setup>
import {Head} from '@inertiajs/vue3';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ImportFile from '@/components/common/ImportFile.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {partPage} from '@/config/parts';
import {index as parts} from '@/routes/parts';
import {store as submitImport} from '@/routes/parts/import';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: partPage.title,
            resourceUrl: parts().url,
        }),
    },
});
</script>

<template>
    <Head title="Import Parts" />

    <div class="space-y-6 p-6">
        <AppPageHeader
            description="Upload a parts spreadsheet to create or update part records."
            title="Import Parts"
        />

        <ImportFile
            accepted-files=".xlsx,.xls,.csv"
            description="Upload the parts spreadsheet to import part records."
            :information-items="[
                'Name is required.',
                'New Part Name, Section, Cutting Size, Part Weight, and Raw Material Weight are optional.',
                'Section names are matched case-insensitively and must exist in the system when provided.',
                'Name + Section identifies an existing part.',
                'Existing images and status values are preserved during updates.',
            ]"
            sample-file="/samples/parts.xlsx"
            sample-file-name="parts.xlsx"
            submit-button-text="Import Parts"
            :submit-url="submitImport().url"
            title="Import Parts"
            upload-description="Drag and drop your parts spreadsheet here, or click to browse."
            upload-text="Upload parts spreadsheet"
        />
    </div>
</template>
