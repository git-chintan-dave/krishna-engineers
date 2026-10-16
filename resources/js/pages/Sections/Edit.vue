<script lang="ts" setup>
import {router, useForm} from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {sectionPage} from '@/config/sections';
import SectionForm from '@/pages/Sections/components/SectionForm.vue';
import {index as sections, update as updateSection} from '@/routes/sections';
import type {Section, SectionFormData} from '@/types/section';

const props = defineProps<{
    section: Section;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
            resource: sectionPage.title,
            resourceUrl: sections().url,
        }),
    },
});

const form = useForm<SectionFormData>({
    name: props.section.name,
    status: props.section.status === 'active',
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
    })).put(updateSection(props.section.uuid).url);
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
        :description="sectionPage.form.editDescription"
        :title="sectionPage.form.editTitle"
    >
        <SectionForm
            :errors="form.errors"
            :form="form"
            :processing="form.processing"
            :submit-label="sectionPage.form.editSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
