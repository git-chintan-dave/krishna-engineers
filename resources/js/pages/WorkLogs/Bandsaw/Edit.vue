<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import BandsawWorkLogForm from '@/pages/WorkLogs/Bandsaw/components/BandsawWorkLogForm.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { bandsawWorkLogPage } from '@/config/bandsawWorkLogs';
import {
    index as bandsawWorkLogs,
    update,
} from '@/routes/bandsaw';
import type {
    BandsawWorkLog,
    BandsawWorkLogFormData,
    BandsawWorkLogLastReport,
    BandsawWorkLogSelectOption,
} from '@/types/bandsawWorkLog';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    bandsawWorkLog: BandsawWorkLog;

    bandsaws: BandsawWorkLogSelectOption<number>[];
    parts: BandsawWorkLogSelectOption<number>[];
    sectionSizes: BandsawWorkLogSelectOption<number>[];

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
        breadcrumbs: useCrudBreadcrumbs('edit', {
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
    user_id: props.bandsawWorkLog.user.id,

    work_date: props.bandsawWorkLog.work_date,

    part_id: props.bandsawWorkLog.part.id,

    section_size_id:
    props.bandsawWorkLog.section_size.id,

    quantity: props.bandsawWorkLog.quantity,

    remarks: props.bandsawWorkLog.remarks ?? '',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.put(update(props.bandsawWorkLog.id).url);
};

const handleCancel = () => {
    router.visit(bandsawWorkLogs().url);
};
</script>

<template>
    <FormLayout
        :title="bandsawWorkLogPage.form.editTitle"
        :description="bandsawWorkLogPage.form.editDescription"
    >
        <BandsawWorkLogForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="bandsawWorkLogPage.form.editSubmitLabel"
            :bandsaws="props.bandsaws"
            :parts="props.parts"
            :section-sizes="props.sectionSizes"
            :current-user-id="props.currentUserId"
            :can-select-bandsaw="props.canSelectBandsaw"
            :last-reports="props.lastReports"
            :is-create="false"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
