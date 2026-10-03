<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import ToolTransactionForm from '@/pages/ToolTransactions/components/ToolTransactionForm.vue';

import { toolTransactionPage } from '@/config/toolTransactions';
import { dashboard } from '@/routes';

import {
    index as toolTransactions,
    store,
} from '@/routes/tool-transactions';

import type { ToolTransactionType } from '@/types/toolTransaction';

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: toolTransactionPage.title,
                href: toolTransactions(),
            },
            {
                title: 'Create',
            },
        ],
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    toolId: null as number | null,

    type: '' as ToolTransactionType | '',

    quantity: null as number | null,

    fromUserId: null as number | null,
    toUserId: null as number | null,

    partId: null as number | null,
    setupId: null as number | null,

    returnScrap: null as boolean | null,

    remarks: '',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.transform((data) => ({
        tool_id: data.toolId,
        type: data.type,
        quantity: data.quantity,
        from_user_id: data.fromUserId,
        to_user_id: data.toUserId,
        part_id: data.partId,
        setup_id: data.setupId,
        return_scrap: data.returnScrap,
        remarks: data.remarks || null,
    })).post(store().url);
};

const handleCancel = () => {
    router.visit(toolTransactions().url);
};
</script>

<template>
    <FormLayout
        :title="toolTransactionPage.form.createTitle"
        :description="
            toolTransactionPage.form.createDescription
        "
    >
        <ToolTransactionForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                toolTransactionPage.form.createSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
