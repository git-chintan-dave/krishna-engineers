<script lang="ts" setup>
import {Form, Head} from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import {Button} from '@/components/ui/button';
import {Checkbox} from '@/components/ui/checkbox';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {Spinner} from '@/components/ui/spinner';
import {store} from '@/routes/login';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your employee code and password below to log in',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Log in"/>

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-slot="{ errors, processing }"
        :reset-on-success="['password']"
        class="flex flex-col gap-6"
        v-bind="store.form()"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="employee_code">Employee Code</Label>

                <Input
                    id="employee_code"
                    v-focus
                    :tabindex="1"
                    autocomplete="username"
                    inputmode="numeric"
                    name="employee_code"
                    placeholder="001"
                    required
                    type="text"
                />

                <InputError :message="errors.employee_code"/>
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                </div>

                <PasswordInput
                    id="password"
                    :tabindex="2"
                    autocomplete="current-password"
                    name="password"
                    placeholder="Password"
                    required
                />

                <InputError :message="errors.password"/>
            </div>

            <div class="flex items-center justify-between">
                <Label class="flex items-center space-x-3" for="remember">
                    <Checkbox id="remember" :tabindex="3" name="remember"/>
                    <span>Remember me</span>
                </Label>
            </div>

            <Button
                :disabled="processing"
                :tabindex="4"
                class="mt-4 w-full"
                data-test="login-button"
                type="submit"
            >
                <Spinner v-if="processing"/>
                Log in
            </Button>
        </div>
    </Form>
</template>
