<script lang="ts" setup>
import {Form, Head} from '@inertiajs/vue3';
import {LockKeyhole, ShieldCheck} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import type {Props as ManagePasskeysProps} from '@/components/ManagePasskeys.vue';
import type {Props as ManageTwoFactorProps} from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import {Button} from '@/components/ui/button';
import {Label} from '@/components/ui/label';
import {dashboard} from '@/routes';
import {update as updateUserPassword} from '@/routes/user-password';

// oxfmt-ignore
type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard().url,
            },
            {
                title: 'Security settings',
            },
        ],
    },
});
</script>

<template>
    <Head title="Security settings"/>

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-8">
        <!-- Password -->
        <section
            class="overflow-hidden rounded-xl border border-border bg-card"
        >
            <div
                class="flex items-start gap-3 border-b border-border px-5 py-5 sm:px-6"
            >
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <LockKeyhole class="size-5"/>
                </div>

                <div class="min-w-0 flex-1">
                    <Heading
                        description="Use a long, unique password to help keep your account secure."
                        title="Update password"
                        variant="small"
                    />
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <Form
                    v-slot="{ errors, processing }"
                    :options="{
                        preserveScroll: true,
                    }"
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-5"
                    reset-on-success
                    v-bind="updateUserPassword()"
                >
                    <div class="grid gap-2">
                        <Label for="current_password"> Current password </Label>
                        <PasswordInput
                            id="current_password"
                            autocomplete="current-password"
                            class="w-full"
                            name="current_password"
                            placeholder="Enter current password"
                        />
                        <InputError :message="errors.current_password"/>
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <PasswordInput
                            id="password"
                            :passwordrules="props.passwordRules"
                            autocomplete="new-password"
                            class="w-full"
                            name="password"
                            placeholder="Enter new password"
                        />
                        <InputError :message="errors.password"/>
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">
                            Confirm new password
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            :passwordrules="props.passwordRules"
                            autocomplete="new-password"
                            class="w-full"
                            name="password_confirmation"
                            placeholder="Re-enter new password"
                        />
                        <InputError :message="errors.password_confirmation"/>
                    </div>

                    <div
                        class="flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="text-sm text-muted-foreground">
                            Choose a password you don't use elsewhere.
                        </p>

                        <Button
                            :disabled="processing"
                            class="w-full sm:w-auto"
                            data-test="update-password-button"
                        >
                            {{ processing ? 'Saving...' : 'Save password' }}
                        </Button>
                    </div>
                </Form>
            </div>
        </section>

        <!-- Two-factor authentication -->
        <section
            class="overflow-hidden rounded-xl border border-border bg-card"
        >
            <div
                class="flex items-start gap-3 border-b border-border px-5 py-5 sm:px-6"
            >
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <ShieldCheck class="size-5"/>
                </div>

                <div class="min-w-0 flex-1">
                    <Heading
                        description="Add an extra layer of protection by requiring a verification code when signing in."
                        title="Two-factor authentication"
                        variant="small"
                    />
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <ManageTwoFactor
                    :canManageTwoFactor="canManageTwoFactor"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />
            </div>
        </section>
    </div>
</template>
