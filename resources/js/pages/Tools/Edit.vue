<script setup lang="ts">
import {router, useForm} from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {toolPage} from '@/config/tools';
import ToolForm from '@/pages/Tools/components/ToolForm.vue';
import {index as tools, update as updateTool,} from '@/routes/tools';
import type {Tool, ToolFormData,} from '@/types/tool';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    tool: Tool;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('edit', {
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
    name: props.tool.name,
    type: props.tool.type,
    image: null,
    remove_image: false,
    status: props.tool.status === 'active',
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
        .put(updateTool(props.tool.id).url);
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
        :title="toolPage.form.editTitle"
        :description="
            toolPage.form.editDescription
        "
    >
        <ToolForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                toolPage.form.editSubmitLabel
            "
            :current-image="props.tool.image"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
