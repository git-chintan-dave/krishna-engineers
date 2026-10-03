<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { rawMaterialTransactionPage } from '@/config/rawMaterialTransactions';
import RawMaterialTransactionForm from '@/pages/RawMaterialTransactions/components/RawMaterialTransactionForm.vue';
import {
    index as rawMaterialTransactions,
    update as updateRawMaterialTransaction,
} from '@/routes/raw-material-transactions';
import type {
    RawMaterialTransaction,
    RawMaterialTransactionFormData,
} from '@/types/rawMaterialTransaction';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    transaction: RawMaterialTransaction;

    sectionSizes: Array<{
        value: number;
        label: string;
    }>;

    transactionTypes: Array<{
        value: string;
        label: string;
    }>;
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: rawMaterialTransactionPage.title,
            resourceUrl: rawMaterialTransactions().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<RawMaterialTransactionFormData>({
    section_size_id: props.transaction.section_size.id,
    type: props.transaction.type.value,
    quantity: props.transaction.quantity,
    reference_type: props.transaction.reference_type ?? '',
    reference_id: props.transaction.reference_id,
    remarks: props.transaction.remarks ?? '',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.put(
        updateRawMaterialTransaction(
            props.transaction.id,
        ).url,
    );
};

const handleCancel = () => {
    router.visit(
        rawMaterialTransactions().url,
    );
};
</script>

<template>
    <FormLayout
        :title="
            rawMaterialTransactionPage.form
                .editTitle
        "
        :description="
            rawMaterialTransactionPage.form
                .editDescription
        "
    >
        <RawMaterialTransactionForm
            :form="form"
            :section-sizes="props.sectionSizes"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                rawMaterialTransactionPage.form
                    .editSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
