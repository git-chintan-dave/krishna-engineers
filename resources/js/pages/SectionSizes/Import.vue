<script lang="ts" setup>
import {Head} from '@inertiajs/vue3';

import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ImportFile from '@/components/common/ImportFile.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {sectionSizePage} from '@/config/sectionSizes';
import {index as sectionSizes} from '@/routes/section-sizes';
import {store as submitImport} from '@/routes/section-sizes/import';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('import', {
            resource: sectionSizePage.title,
            resourceUrl: sectionSizes().url,
        }),
    },
});
</script>

<template>
    <Head title="Import Section Sizes" />

    <div class="space-y-6 p-6">
        <AppPageHeader
            description="Upload section sizes to create or update size records."
            title="Import Section Sizes"
        />

        <ImportFile
            accepted-files=".xlsx,.xls,.csv"
            description="Upload the section size spreadsheet."
            :index-url="sectionSizes().url"
            :information-items="[
                'Section Name and Size are required.',
                'Section names are matched case-insensitively and must exist in the system.',
                'Weight is optional.',
                'Section + Size identifies an existing record.',
                'Existing stock quantities are preserved during updates.',
            ]"
            sample-file="/samples/section-sizes.xlsx"
            sample-file-name="section-sizes.xlsx"
            submit-button-text="Import Section Sizes"
            :submit-url="submitImport().url"
            title="Import Section Sizes"
            upload-description="Drag and drop your section size spreadsheet here, or click to browse."
            upload-text="Upload section size spreadsheet"
        />
    </div>
</template>
