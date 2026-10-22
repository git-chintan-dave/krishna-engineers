<script lang="ts" setup>
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { sectionPage } from '@/config/sections';
import SectionForm from '@/pages/Sections/components/SectionForm.vue';
import { index as sections, store } from '@/routes/sections';
import type { SectionFormData } from '@/types/section';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: sectionPage.title,
            resourceUrl: sections().url,
        }),
    },
});

const form = useForm<SectionFormData>({
    name: '',
    status: true,
});

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form.transform((data) => ({
        ...data,
        status: data.status ? 'active' : 'inactive',
    })).post(store().url);
};

/*
|--------------------------------------------------------------------------
| Cancel
|--------------------------------------------------------------------------
*/

const handleCancel = () => {
    router.visit(sections().url);
};
</script>

<template>
    <FormLayout
        :description="sectionPage.form.createDescription"
        :title="sectionPage.form.createTitle"
    >
        <SectionForm
            :errors="form.errors"
            :form="form"
            :processing="form.processing"
            :submit-label="sectionPage.form.createSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
