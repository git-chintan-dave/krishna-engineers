<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { setupPage } from '@/config/setups';
import SetupForm from '@/pages/Setups/components/SetupForm.vue';
import { index as setups, store } from '@/routes/setups';
import type { SetupFormData } from '@/types/setup';

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: setupPage.title,
            resourceUrl: setups().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<SetupFormData>({
    name: '',
    status: true,
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.transform((data) => ({
        ...data,
        status: data.status ? 'active' : 'inactive',
    })).post(store().url);
};

const handleCancel = () => {
    router.visit(setups().url);
};
</script>

<template>
    <FormLayout
        :title="setupPage.form.createTitle"
        :description="setupPage.form.createDescription"
    >
        <SetupForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="setupPage.form.createSubmitLabel"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
