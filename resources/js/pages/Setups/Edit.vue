<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import FormLayout from '@/components/layouts/FormLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { setupPage } from '@/config/setups';
import SetupForm from '@/pages/Setups/components/SetupForm.vue';
import {
    index as setups,
    update as updateSetup,
} from '@/routes/setups';
import type {
    Setup,
    SetupFormData,
} from '@/types/setup';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    setup: Setup;
}>();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs(
            'edit',
            {
                resource: setupPage.title,
                resourceUrl: setups().url,
            },
        ),
    },
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm<SetupFormData>({
    name: props.setup.name,
    status: props.setup.status === 'active',
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    form
        .transform((data) => ({
            ...data,
            status: data.status
                ? 'active'
                : 'inactive',
        }))
        .put(
            updateSetup(props.setup.id).url,
        );
};

const handleCancel = () => {
    router.visit(setups().url);
};
</script>

<template>
    <FormLayout
        :title="setupPage.form.editTitle"
        :description="
            setupPage.form.editDescription
        "
    >
        <SetupForm
            :form="form"
            :errors="form.errors"
            :processing="form.processing"
            :submit-label="
                setupPage.form.editSubmitLabel
            "
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </FormLayout>
</template>
