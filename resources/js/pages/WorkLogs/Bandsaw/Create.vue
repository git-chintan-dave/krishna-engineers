<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { bandsawWorkLogPage } from '@/config/bandsawWorkLogs';
import {
    index as bandsawWorkLogs,
    store,
} from '@/routes/bandsaw';
import BandsawWorkLogForm from '@/pages/WorkLogs/Bandsaw/components/BandsawWorkLogForm.vue';
import type {
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
    user_id: props.canSelectBandsaw
        ? null
        : props.currentUserId,

    work_date: new Date().toISOString().slice(0, 10),

    part_id: null,
    section_size_id: null,

    quantity: 1,

    remarks: '',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.post(store().url);
};

const handleCancel = () => {
    router.visit(bandsawWorkLogs().url);
};
</script>

<template>
    <FormLayout
        :title="bandsawWorkLogPage.form.createTitle"
        :description="bandsawWorkLogPage.form.createDescription"
    >
        <BandsawWorkLogForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="bandsawWorkLogPage.form.createSubmitLabel"
            :bandsaws="props.bandsaws"
            :parts="props.parts"
            :section-sizes="props.sectionSizes"
            :current-user-id="props.currentUserId"
            :can-select-bandsaw="props.canSelectBandsaw"
            :last-reports="props.lastReports"
            :is-create="true"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
