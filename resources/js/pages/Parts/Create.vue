<script lang="ts" setup>
import {router, useForm} from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {partPage} from '@/config/parts';
import PartForm from '@/pages/Parts/components/PartForm.vue';
import {index as parts, store} from '@/routes/parts';
import type {PartFormData} from '@/types/part';

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('create', {
            resource: partPage.title,
            resourceUrl: parts().url,
        }),
    },
});

const form = useForm<PartFormData>({
    name: '',
    new_part_name: '',
    section_id: null,
    image: null,
    remove_image: false,
    cutting_size: '',
    part_weight: '',
    raw_material_weight: '',
    status: true,
});

const handleSubmit = () => {
    form.transform((data) => ({
        ...data,
        status: data.status ? 'active' : 'inactive',
    })).post(store().url);
};

const handleCancel = () => {
    router.visit(parts().url);
};
</script>

<template>
    <FormLayout
        :description="partPage.form.createDescription"
        :title="partPage.form.createTitle"
    >
        <PartForm
            :errors="form.errors"
            :form="form"
            :processing="form.processing"
            :submit-label="partPage.form.createSubmitLabel"
            @cancel="handleCancel"
            @submit="handleSubmit"
        />
    </FormLayout>
</template>
