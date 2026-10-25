<script lang="ts" setup>
import {Form, Head, Link, usePage} from '@inertiajs/vue3';
import {computed} from 'vue';
import {ArrowUpRight, Fingerprint, Phone, UserRound,} from '@lucide/vue';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {send} from '@/routes/verification';
import {dashboard} from "@/routes";
import {update as updateProfile} from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard().url,
            },
            {
                title: 'Profile settings',
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="space-y-8">
        <Heading
            title="Profile"
            description="Manage your personal information and contact details."
            variant="small"
        />

        <Form
            v-slot="{ errors, processing }"
            class="space-y-6"
            v-bind="updateProfile()"
        >
            <section
                class="overflow-hidden rounded-xl border border-border bg-card"
            >
                <div class="flex items-start gap-3 border-b border-border px-5 py-4 sm:px-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <UserRound class="size-5" />
                    </div>

                    <div class="min-w-0">
                        <h2 class="font-semibold text-foreground">
                            Personal information
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Your name and primary email address.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <div class="grid gap-2">
                        <Label for="name">Full name</Label>
                        <Input
                            id="name"
                            :default-value="user.name"
                            autocomplete="name"
                            name="name"
                            placeholder="Enter your full name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            :default-value="user.email"
                            autocomplete="email"
                            name="email"
                            placeholder="Enter your email address"
                            type="email"
                        />
                        <InputError :message="errors.email" />

                        <div
                            v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                            class="text-sm text-muted-foreground"
                        >
                            Email not verified.
                            <Link
                                :href="send()"
                                as="button"
                                class="font-medium text-primary underline underline-offset-4"
                            >
                                Resend verification
                                <ArrowUpRight class="inline size-3.5" />
                            </Link>

                            <p
                                v-if="page.props.status === 'verification-link-sent'"
                                class="mt-2 font-medium text-green-600"
                            >
                                Verification link sent successfully.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-border bg-card"
            >
                <div class="flex items-start gap-3 border-b border-border px-5 py-4 sm:px-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Phone class="size-5" />
                    </div>

                    <div class="min-w-0">
                        <h2 class="font-semibold text-foreground">
                            Contact details
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Phone numbers used for contact and communication.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <div class="grid gap-2">
                        <Label for="phone_number">Phone number</Label>
                        <Input
                            id="phone_number"
                            :default-value="user.phone_number"
                            autocomplete="tel"
                            inputmode="numeric"
                            maxlength="10"
                            name="phone_number"
                            placeholder="10-digit phone number"
                            type="tel"
                        />
                        <InputError :message="errors.phone_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="alternate_phone_number">
                            Alternate phone number
                            <span class="font-normal text-muted-foreground">
                                (Optional)
                            </span>
                        </Label>
                        <Input
                            id="alternate_phone_number"
                            :default-value="user.alternate_phone_number"
                            autocomplete="tel"
                            inputmode="numeric"
                            maxlength="10"
                            name="alternate_phone_number"
                            placeholder="Alternate phone number"
                            type="tel"
                        />
                        <InputError
                            :message="errors.alternate_phone_number"
                        />
                    </div>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-border bg-card"
            >
                <div class="flex items-start gap-3 border-b border-border px-5 py-4 sm:px-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Fingerprint class="size-5" />
                    </div>

                    <div class="min-w-0">
                        <h2 class="font-semibold text-foreground">
                            Identity information
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Keep your Aadhaar number private.
                        </p>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="grid max-w-md gap-2">
                        <Label for="aadhar_number">Aadhaar number</Label>
                        <Input
                            id="aadhar_number"
                            :default-value="user.aadhar_number"
                            autocomplete="off"
                            inputmode="numeric"
                            maxlength="12"
                            name="aadhar_number"
                            placeholder="12-digit Aadhaar number"
                            type="password"
                        />
                        <InputError :message="errors.aadhar_number" />
                        <p class="text-xs text-muted-foreground">
                            Your number is masked while displayed in the field.
                        </p>
                    </div>
                </div>
            </section>

            <div
                class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-muted-foreground">
                    Save your changes when you're finished.
                </p>

                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="update-profile-button"
                >
                    {{ processing ? 'Saving changes...' : 'Save changes' }}
                </Button>
            </div>
        </Form>

        <div class="border-t border-border" />

        <DeleteUser />
    </div>
</template>
