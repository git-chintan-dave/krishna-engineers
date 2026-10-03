<script setup lang="ts">
import { X } from '@lucide/vue';

defineProps<{
    open: boolean;
    src: string | null;
    alt?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const close = () => {
    emit('update:open', false);
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open && src"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
            @click.self="close"
        >
            <button
                type="button"
                class="absolute right-4 top-4 inline-flex size-9 items-center justify-center rounded-md border border-white/20 bg-black/40 text-white transition hover:bg-black/60"
                aria-label="Close image"
                title="Close image"
                @click="close"
            >
                <X class="size-5" />
            </button>

            <img
                :src="src"
                :alt="alt ?? 'Image preview'"
                class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain shadow-2xl"
            />
        </div>
    </Teleport>
</template>
