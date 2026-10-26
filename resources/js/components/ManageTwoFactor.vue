<script lang="ts" setup>
import { Form } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';

import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-5">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                    :class="
                        twoFactorEnabled
                            ? 'bg-green-500/10 text-green-600 dark:text-green-400'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    <ShieldCheck class="size-5" />
                </div>

                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-medium text-foreground">
                            Two-factor authentication
                        </h3>

                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="
                                twoFactorEnabled
                                    ? 'bg-green-500/10 text-green-700 dark:text-green-400'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{ twoFactorEnabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>

                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{
                            twoFactorEnabled
                                ? 'Your account has an extra layer of protection during sign-in.'
                                : 'Protect your account with a verification code from an authenticator app.'
                        }}
                    </p>
                </div>
            </div>

            <div class="shrink-0 sm:pt-1">
                <Button
                    v-if="twoFactorEnabled"
                    variant="destructive"
                    class="w-full sm:w-auto"
                    @click="() => {}"
                >
                    Disable 2FA
                </Button>

                <Button
                    v-else-if="hasSetupData"
                    class="w-full sm:w-auto"
                    @click="showSetupModal = true"
                >
                    <ShieldCheck class="mr-2 size-4" />
                    Continue setup
                </Button>

                <Form
                    v-else
                    v-slot="{ processing }"
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                >
                    <Button
                        type="submit"
                        class="w-full sm:w-auto"
                        :disabled="processing"
                    >
                        <ShieldCheck class="mr-2 size-4" />
                        {{ processing ? 'Enabling...' : 'Enable 2FA' }}
                    </Button>
                </Form>
            </div>
        </div>

        <div
            v-if="twoFactorEnabled"
            class="space-y-4 border-t border-border pt-5"
        >
            <p class="text-sm leading-relaxed text-muted-foreground">
                Use your authenticator app to retrieve the verification code
                whenever you sign in.
            </p>

            <Form v-slot="{ processing }" v-bind="disable.form()">
                <Button
                    type="submit"
                    variant="destructive"
                    class="w-full sm:w-auto"
                    :disabled="processing"
                >
                    {{ processing ? 'Disabling...' : 'Disable 2FA' }}
                </Button>
            </Form>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
