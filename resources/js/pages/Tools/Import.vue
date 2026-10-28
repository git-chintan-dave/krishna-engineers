<script lang="ts" setup>
import {Head} from '@inertiajs/vue3';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ImportFile from '@/components/common/ImportFile.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {toolPage} from '@/config/tools';
import {index as tools} from '@/routes/tools';
import {store as submitImport} from '@/routes/tools/import';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: toolPage.title,
            resourceUrl: tools().url,
        }),
    },
});
</script>

<template>
    <Head title="Import Tools" />

    <div class="space-y-6 p-6">
        <AppPageHeader
            description="Upload a tool spreadsheet to create new tool records."
            title="Import Tools"
        />

        <ImportFile
            accepted-files=".xlsx,.xls,.csv"
            description="Upload the tool spreadsheet to import tool records."
            :index-url="tools().url"
            :information-items="[
                'Type and Name are required.',
                'Tool types are matched case-insensitively against the available tool types.',
                'Type + Name identifies an existing tool.',
                'Existing tools, including image, quantity, and status, are preserved.',
                'New tools are created with zero quantity and active status.',
            ]"
            sample-file="/samples/tools.xlsx"
            sample-file-name="tools.xlsx"
            submit-button-text="Import Tools"
            :submit-url="submitImport().url"
            title="Import Tools"
            upload-description="Drag and drop your tool spreadsheet here, or click to browse."
            upload-text="Upload tool spreadsheet"
        />
    </div>
</template>
