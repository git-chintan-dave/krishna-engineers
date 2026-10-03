<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import ToolTransactionForm from '@/pages/ToolTransactions/components/ToolTransactionForm.vue';

import { toolTransactionPage } from '@/config/toolTransactions';
import { dashboard } from '@/routes';

import {
    index as toolTransactions,
    update,
} from '@/routes/tool-transactions';

import type {
    ToolTransaction,
    ToolTransactionType,
} from '@/types/toolTransaction';

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
                title: 'Edit',
            },
        ],
    },
});

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    transaction: ToolTransaction;
}>();

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    toolId: props.transaction.tool?.id ?? null,

    type:
        props.transaction.type?.value ??
        ('' as ToolTransactionType | ''),

    quantity: props.transaction.quantity,

    fromUserId: props.transaction.fromUser?.id ?? null,

    toUserId: props.transaction.toUser?.id ?? null,

    partId: props.transaction.part?.id ?? null,

    setupId: props.transaction.setup?.id ?? null,

    returnScrap: props.transaction.returnScrap,

    remarks: props.transaction.remarks ?? '',
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
    })).put(
        update(props.transaction.id).url,
    );
};

const handleCancel = () => {
    router.visit(toolTransactions().url);
};
</script>

<template>
    <FormLayout
        :title="toolTransactionPage.form.editTitle"
        :description="
            toolTransactionPage.form.editDescription
        "
    >
        <ToolTransactionForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                toolTransactionPage.form.editSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
