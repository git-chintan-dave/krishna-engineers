<script lang="ts" setup>
import {Form} from '@inertiajs/vue3';
import {useClipboard} from '@vueuse/core';
import {Check, Copy, ScanLine, ShieldCheck} from '@lucide/vue';
import {computed, nextTick, ref, useTemplateRef, watch} from 'vue';

import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle,} from '@/components/ui/dialog';
import {InputOTP, InputOTPGroup, InputOTPSlot,} from '@/components/ui/input-otp';
import {Spinner} from '@/components/ui/spinner';
import {useAppearance} from '@/composables/useAppearance';
import {useTwoFactorAuth} from '@/composables/useTwoFactorAuth';
import {confirm} from '@/routes/two-factor';
import type {TwoFactorConfigContent} from '@/types';

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { resolvedAppearance } = useAppearance();
const { copy, copied } = useClipboard();

const {
    qrCodeSvg,
    manualSetupKey,
    clearSetupData,
    fetchSetupData,
    errors,
} = useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: 'Two-factor authentication enabled',
            description:
                'Your account is protected with an additional verification step.',
            buttonText: 'Close',
        };
    }

    if (showVerificationStep.value) {
        return {
            title: 'Verify authentication code',
            description:
                'Enter the current six-digit code from your authenticator app to finish setup.',
            buttonText: 'Confirm',
        };
    }

    return {
        title: 'Set up two-factor authentication',
        description:
            'Scan the QR code with your authenticator app, or enter the setup key manually.',
        buttonText: 'Continue',
    };
});

const handleModalNextStep = (): void => {
    if (props.twoFactorEnabled) {
        isOpen.value = false;
        return;
    }

    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = (): void => {
    showVerificationStep.value = false;
    code.value = '';

    if (props.twoFactorEnabled) {
        clearSetupData();
    }
};

const handleVerificationBack = (): void => {
    code.value = '';
    showVerificationStep.value = false;
};

watch(
    () => isOpen.value,
    async (open) => {
        if (!open) {
            resetModalState();
            return;
        }

        showVerificationStep.value = false;
        code.value = '';

        await fetchSetupData();
    },
);
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-md">
            <div
                class="flex flex-col items-center border-b border-border px-6 pb-6 pt-7"
            >
                <div
                    class="mb-4 flex size-14 items-center justify-center rounded-2xl border border-border bg-muted text-foreground shadow-sm"
                >
                    <ShieldCheck class="size-7" />
                </div>

                <DialogHeader class="items-center space-y-2">
                    <DialogTitle class="text-center text-xl">
                        {{ modalConfig.title }}
                    </DialogTitle>

                    <DialogDescription
                        class="max-w-sm text-center leading-relaxed"
                    >
                        {{ modalConfig.description }}
                    </DialogDescription>
                </DialogHeader>
            </div>

            <div class="space-y-5 p-6">
                <template v-if="!showVerificationStep">
                    <AlertError v-if="errors.length" :errors="errors" />

                    <template v-else>
                        <div
                            v-if="!props.twoFactorEnabled"
                            class="space-y-5"
                        >
                            <div
                                class="mx-auto flex size-64 max-w-full items-center justify-center overflow-hidden rounded-xl border border-border bg-white p-4"
                            >
                                <div
                                    v-if="!qrCodeSvg"
                                    class="flex size-full items-center justify-center"
                                >
                                    <Spinner class="size-7 text-muted-foreground" />
                                </div>

                                <div
                                    v-else
                                    class="flex size-full items-center justify-center"
                                    :style="{
                                        filter:
                                            resolvedAppearance === 'dark'
                                                ? 'invert(1) brightness(1.5)'
                                                : undefined,
                                    }"
                                    v-html="qrCodeSvg"
                                />
                            </div>

                            <div class="space-y-2 text-center">
                                <p class="text-sm font-medium">
                                    Scan with your authenticator app
                                </p>
                                <p
                                    class="text-sm leading-relaxed text-muted-foreground"
                                >
                                    Open an app such as Google Authenticator or
                                    Microsoft Authenticator and scan this QR
                                    code.
                                </p>
                            </div>

                            <div class="relative flex items-center gap-3">
                                <div class="h-px flex-1 bg-border" />
                                <span
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    OR ENTER KEY MANUALLY
                                </span>
                                <div class="h-px flex-1 bg-border" />
                            </div>

                            <div class="space-y-2">
                                <label
                                    class="text-sm font-medium text-foreground"
                                >
                                    Setup key
                                </label>

                                <div
                                    class="flex overflow-hidden rounded-lg border border-border bg-muted/30"
                                >
                                    <div
                                        class="min-w-0 flex-1 overflow-x-auto px-3 py-3 font-mono text-sm break-all select-all"
                                    >
                                        <span
                                            v-if="manualSetupKey"
                                            class="text-foreground"
                                        >
                                            {{ manualSetupKey }}
                                        </span>

                                        <span
                                            v-else
                                            class="flex items-center gap-2 text-muted-foreground"
                                        >
                                            <Spinner class="size-4" />
                                            Loading setup key...
                                        </span>
                                    </div>

                                    <Button
                                        v-if="manualSetupKey"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="m-1 shrink-0"
                                        :aria-label="
                                            copied
                                                ? 'Setup key copied'
                                                : 'Copy setup key'
                                        "
                                        @click="copy(manualSetupKey)"
                                    >
                                        <Check
                                            v-if="copied"
                                            class="size-4 text-green-600"
                                        />
                                        <Copy v-else class="size-4" />
                                    </Button>
                                </div>

                                <p class="text-xs leading-relaxed text-muted-foreground">
                                    Keep this key private. Anyone who has it
                                    may be able to generate your verification
                                    codes.
                                </p>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center gap-3 rounded-xl border border-border bg-muted/30 p-5 text-center"
                        >
                            <div
                                class="flex size-11 items-center justify-center rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                            >
                                <Check class="size-5" />
                            </div>

                            <p class="font-medium">
                                Two-factor authentication is enabled
                            </p>

                            <p
                                class="text-sm leading-relaxed text-muted-foreground"
                            >
                                Your account requires an authenticator code
                                when signing in.
                            </p>
                        </div>

                        <Button
                            v-if="props.twoFactorEnabled"
                            class="w-full"
                            @click="handleModalNextStep"
                        >
                            Close
                        </Button>

                        <Button
                            v-else
                            class="w-full"
                            :disabled="!qrCodeSvg || !manualSetupKey"
                            @click="handleModalNextStep"
                        >
                            Continue
                        </Button>
                    </template>
                </template>

                <template v-else>
                    <Form
                        v-slot="{ errors: formErrors, processing }"
                        error-bag="confirmTwoFactorAuthentication"
                        reset-on-error
                        v-bind="confirm.form()"
                        @finish="code = ''"
                        @success="isOpen = false"
                    >
                        <input
                            :value="code"
                            name="code"
                            type="hidden"
                        />

                        <div
                            ref="pinInputContainerRef"
                            class="space-y-5"
                        >
                            <div
                                class="flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <ScanLine class="size-6" />
                            </div>

                            <div class="space-y-1.5">
                                <h3 class="font-semibold">
                                    Enter verification code
                                </h3>
                                <p
                                    class="text-sm leading-relaxed text-muted-foreground"
                                >
                                    Enter the six-digit code currently shown
                                    in your authenticator app.
                                </p>
                            </div>

                            <div
                                class="flex flex-col items-center gap-3 py-2"
                            >
                                <InputOTP
                                    id="otp"
                                    v-model="code"
                                    :disabled="processing"
                                    :maxlength="6"
                                    inputmode="numeric"
                                    autofocus
                                >
                                    <InputOTPGroup>
                                        <InputOTPSlot
                                            v-for="index in 6"
                                            :key="index"
                                            :index="index - 1"
                                        />
                                    </InputOTPGroup>
                                </InputOTP>

                                <InputError :message="formErrors?.code" />
                            </div>

                            <AlertError
                                v-if="formErrors && Object.keys(formErrors).some((key) => key !== 'code')"
                                :errors="Object.entries(formErrors)
                                    .filter(([key]) => key !== 'code')
                                    .map(([, message]) => String(message))"
                            />

                            <div class="flex gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="flex-1"
                                    :disabled="processing"
                                    @click="handleVerificationBack"
                                >
                                    Back
                                </Button>

                                <Button
                                    type="submit"
                                    class="flex-1"
                                    :disabled="
                                        processing ||
                                        !/^\d{6}$/.test(code)
                                    "
                                >
                                    <Spinner
                                        v-if="processing"
                                        class="mr-2 size-4"
                                    />
                                    {{
                                        processing
                                            ? 'Verifying...'
                                            : 'Verify and enable'
                                    }}
                                </Button>
                            </div>
                        </div>
                    </Form>
                </template>
            </div>
        </DialogContent>
    </Dialog>
</template>
