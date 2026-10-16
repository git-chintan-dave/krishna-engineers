<script lang="ts" setup>
import {onBeforeUnmount, watch} from 'vue';
import {X} from '@lucide/vue';

// Props
const props = defineProps<{
    open: boolean;
    src: string | null;
    alt?: string;
}>();

// Emits
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

// Actions
const close = () => {
    emit('update:open', false);
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        close();
    }
};

// Watchers
watch(
    () => props.open,
    (open) => {
        if (open) {
            document.addEventListener('keydown', handleKeydown);

            document.body.style.overflow = 'hidden';

            return;
        }

        document.removeEventListener('keydown', handleKeydown);

        document.body.style.overflow = '';
    },
);

// Lifecycle
onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);

    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open && src"
            :aria-label="alt ?? 'Image preview'"
            aria-modal="true"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
            role="dialog"
            @click.self="close"
        >
            <button
                aria-label="Close image"
                class="absolute top-4 right-4 inline-flex size-10 items-center justify-center rounded-md border border-white/20 bg-black/40 text-white transition hover:bg-black/60 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black focus-visible:outline-none"
                title="Close image"
                type="button"
                @click="close"
            >
                <X class="size-5"/>
            </button>

            <img
                :alt="alt ?? 'Image preview'"
                :src="src"
                class="max-h-[calc(100vh-2rem)] max-w-[calc(100vw-2rem)] rounded-md object-contain shadow-2xl sm:max-h-[90vh] sm:max-w-[90vw]"
                decoding="async"
            />
        </div>
    </Teleport>
</template>
