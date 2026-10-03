<script setup lang="ts">
import {router, useForm} from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {toolPage} from '@/config/tools';
import ToolForm from '@/pages/Tools/components/ToolForm.vue';
import {index as tools, store} from '@/routes/tools';
import type {ToolFormData,} from '@/types/tool';

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: toolPage.title,
            resourceUrl: tools().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<ToolFormData>({
    name: '',
    type: null,
    image: null,
    remove_image: false,
    status: true,
});

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form
        .transform((data) => ({
            ...data,
            status: data.status ? 'active' : 'inactive',
        }))
        .post(store().url);
};

/*
|--------------------------------------------------------------------------
| Cancel
|--------------------------------------------------------------------------
*/

const handleCancel = () => {
    router.visit(tools().url);
};
</script>

<template>
    <FormLayout
        :title="toolPage.form.createTitle"
        :description="
            toolPage.form.createDescription
        "
    >
        <ToolForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                toolPage.form.createSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
