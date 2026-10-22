<script lang="ts" setup>
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import BandsawWorkLogForm from '@/pages/WorkLogs/Bandsaw/components/BandsawWorkLogForm.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { bandsawWorkLogPage } from '@/config/bandsawWorkLogs';
import { index as bandsawWorkLogs, store } from '@/routes/bandsaw';
import type {
    BandsawWorkLogFormData,
    BandsawWorkLogLastReport,
} from '@/types/bandsawWorkLog';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    currentUserId: number;
    canSelectBandsaw: boolean;
    lastReports: Record<string, BandsawWorkLogLastReport>;
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: bandsawWorkLogPage.title,
            resourceUrl: bandsawWorkLogs().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<BandsawWorkLogFormData>({
    user_id: props.canSelectBandsaw ? null : props.currentUserId,

    work_date: new Date().toISOString().slice(0, 10),

    part_id: null,
    section_id: null,
    section_size_id: null,

    quantity: 1,

    remarks: '',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = (): void => {
    form.post(store().url);
};

const handleCancel = (): void => {
    router.visit(bandsawWorkLogs().url);
};
</script>

<template>
    <FormLayout
        :description="bandsawWorkLogPage.form.createDescription"
        :title="bandsawWorkLogPage.form.createTitle"
    >
        <BandsawWorkLogForm
            :can-select-bandsaw="props.canSelectBandsaw"
            :errors="form.errors"
            :form="form"
            :is-create="true"
            :last-reports="props.lastReports"
            :processing="form.processing"
            :submit-label="bandsawWorkLogPage.form.createSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
