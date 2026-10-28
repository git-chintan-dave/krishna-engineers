<script lang="ts" setup>
import {Head} from '@inertiajs/vue3';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ImportFile from '@/components/common/ImportFile.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {sectionPage} from '@/config/sections';
import {index as sections} from '@/routes/sections';
import {store as submitImport} from '@/routes/sections/import';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: sectionPage.title,
            resourceUrl: sections().url,
        }),
    },
});
</script>

<template>
    <Head title="Import Sections" />

    <div class="space-y-6 p-6">
        <AppPageHeader
            description="Upload a section spreadsheet to create or update section records."
            title="Import Sections"
        />

        <ImportFile
            accepted-files=".xlsx,.xls,.csv"
            description="Upload the section spreadsheet to import section records."
            :index-url="sections().url"
            :information-items="[
                'Name is required.',
                'Section names are used to identify existing records.',
                'Duplicate section names within the uploaded file are not allowed.',
            ]"
            sample-file="/samples/sections.xlsx"
            sample-file-name="sections.xlsx"
            submit-button-text="Import Sections"
            :submit-url="submitImport().url"
            title="Import Sections"
            upload-description="Drag and drop your section spreadsheet here, or click to browse."
            upload-text="Upload section spreadsheet"
        />
    </div>
</template>
