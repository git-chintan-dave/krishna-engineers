<script lang="ts" setup>
import {router, useForm} from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {rawMaterialTransactionPage} from '@/config/rawMaterialTransactions';
import RawMaterialTransactionForm from '@/pages/RawMaterialTransactions/components/RawMaterialTransactionForm.vue';
import {index as rawMaterialTransactions, store,} from '@/routes/raw-material-transactions';
import type {RawMaterialTransactionFormData} from '@/types/rawMaterialTransaction';

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
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
    section_size_id: null,
    type: null,
    quantity: 1,
    reference_type: '',
    reference_id: null,
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
    router.visit(rawMaterialTransactions().url);
};
</script>

<template>
    <FormLayout
        :description="rawMaterialTransactionPage.form.createDescription"
        :title="rawMaterialTransactionPage.form.createTitle"
    >
        <RawMaterialTransactionForm
            :errors="form.errors"
            :form="form"
            :processing="form.processing"
            :submit-label="rawMaterialTransactionPage.form.createSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
