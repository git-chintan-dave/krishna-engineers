<script lang="ts" setup>
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import ToolTransactionForm from '@/pages/ToolTransactions/components/ToolTransactionForm.vue';

import { toolTransactionPage } from '@/config/toolTransactions';
import { dashboard } from '@/routes';
import { index as toolTransactions, update } from '@/routes/tool-transactions';

import type {
    ToolTransaction,
    ToolTransactionType,
} from '@/types/toolTransaction';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: toolTransactionPage.title,
                href: toolTransactions(),
            },
            { title: 'Edit' },
        ],
    },
});

const props = defineProps<{
    transaction: ToolTransaction;
}>();

const form = useForm({
    toolId: props.transaction.tool?.id ?? null,
    type: props.transaction.type?.value ?? ('' as ToolTransactionType | ''),
    quantity: props.transaction.quantity,
    fromUserId: props.transaction.from_user?.id ?? null,
    toUserId: props.transaction.to_user?.id ?? null,
    partId: props.transaction.part?.id ?? null,
    setupId: props.transaction.setup?.id ?? null,
    returnScrap: props.transaction.return_scrap,
    remarks: props.transaction.remarks ?? '',
});

const handleSubmit = () => {
    form.transform((data) => ({
        tool_id: data.toolId,
        type: data.type,
        quantity: data.quantity,
        from_user_id: data.fromUserId,
        to_user_id: data.toUserId,
        part_id: data.partId,
        setup_id: data.setupId,
        return_scrap: data.type === 'issue' ? data.returnScrap : null,
        remarks: data.remarks || null,
    })).put(update(props.transaction.uuid).url);
};

const handleCancel = () => {
    router.visit(toolTransactions().url);
};
</script>

<template>
    <FormLayout
        :description="toolTransactionPage.form.editDescription"
        :title="toolTransactionPage.form.editTitle"
    >
        <ToolTransactionForm
            :errors="form.errors"
            :form="form"
            :processing="form.processing"
            :submit-label="toolTransactionPage.form.editSubmitLabel"
            is-editing
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
