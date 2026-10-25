<script lang="ts" setup>
import {router} from '@inertiajs/vue3';
import {KeyRound} from '@lucide/vue';

import {destroy} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import type {Passkey} from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

withDefaults(defineProps<Props>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const handleDelete = (id: number, onError: () => void) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
        onError,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <div v-if="canManagePasskeys" class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm leading-relaxed text-muted-foreground">
                Sign in securely without entering your password using a
                passkey saved on your device or password manager.
            </p>

            <PasskeyRegister />
        </div>

        <div
            v-if="passkeys.length"
            class="overflow-hidden rounded-lg border border-border"
        >
            <PasskeyItem
                v-for="passkey in passkeys"
                :key="passkey.id"
                :passkey="passkey"
                @remove="handleDelete"
            />
        </div>

        <div
            v-else
            class="flex flex-col items-center rounded-lg border border-dashed border-border px-5 py-8 text-center"
        >
            <div
                class="mb-3 flex size-12 items-center justify-center rounded-xl bg-muted"
            >
                <KeyRound class="size-6 text-muted-foreground" />
            </div>

            <p class="font-medium text-foreground">
                No passkeys registered
            </p>

            <p class="mt-1 max-w-sm text-sm leading-relaxed text-muted-foreground">
                Add a passkey to make future sign-ins faster and more secure.
            </p>
        </div>
    </div>
</template>
