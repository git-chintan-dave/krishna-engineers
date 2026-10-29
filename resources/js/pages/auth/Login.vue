<script lang="ts" setup>
import {Form, Head, useForm} from '@inertiajs/vue3';
import {LockKeyhole, UserRound,} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import {Checkbox} from '@/components/ui/checkbox';
import {Label} from '@/components/ui/label';
import {useZodFormValidation} from '@/composables/useZodFormValidation';
import {loginSchema} from '@/schemas/loginSchema';
import {store} from '@/routes/login';
import {Button} from "@/components/ui/button";
import {Spinner} from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description:
            'Enter your employee code and password below to log in',
    },
});

const props = defineProps<{
    status?: string;
}>();

const form = useForm({
    employee_code: '',
    password: '',
    remember: false,
});

const {errors: frontendErrors, validate} = useZodFormValidation(
    loginSchema,
    () => form,
);

const handleSubmit = () => {
    if (!validate()) {
        return;
    }

    form.submit(store(), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Log in"/>

    <div
        v-if="props.status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ props.status }}
    </div>

    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <div class="grid gap-5">
            <!-- Employee Code -->
            <AppField
                input-id="employee-code"
                label="Employee Code"
                required
            >
                <AppInput
                    id="employee-code"
                    v-model="form.employee_code"
                    v-focus
                    :disabled="form.processing"
                    :error="
                        frontendErrors.employee_code ??
                        form.errors.employee_code
                    "
                    :icon="UserRound"
                    autocomplete="username"
                    inputmode="numeric"
                    placeholder="0001"
                    tabindex="1"
                    type="text"
                />
            </AppField>

            <!-- Password -->
            <AppField
                input-id="employee-password"
                label="Password"
                required
            >
                <AppInput
                    id="employee-password"
                    v-model="form.password"
                    :disabled="form.processing"
                    :error="
                        frontendErrors.password ??
                        form.errors.password
                    "
                    :icon="LockKeyhole"
                    autocomplete="current-password"
                    placeholder="Password"
                    tabindex="2"
                    type="password"
                />
            </AppField>

            <!-- Remember Me -->
            <div class="flex items-center">
                <Label
                    class="flex cursor-pointer items-center gap-3"
                    for="remember"
                >
                    <Checkbox
                        id="remember"
                        v-model="form.remember"
                        :disabled="form.processing"
                        :tabindex="3"
                    />

                    <span>Remember me</span>
                </Label>
            </div>

            <!-- Submit -->
            <Button
                :disabled="form.processing"
                :tabindex="4"
                class="mt-2 w-full"
                data-test="login-button"
                type="submit"
            >
                <Spinner v-if="form.processing"/>
                <span>Log in</span>
            </Button>
        </div>
    </form>
</template>
