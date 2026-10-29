<script lang="ts" setup>
import {ArrowUp} from '@lucide/vue';
import {onMounted, onUnmounted, ref} from 'vue';

const props = withDefaults(
    defineProps<{
        threshold?: number;
    }>(),
    {
        threshold: 300,
    },
);

const isVisible = ref(false);

function updateVisibility(): void {
    isVisible.value = window.scrollY > props.threshold;
}

function scrollToTop(): void {
    const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    window.scrollTo({
        top: 0,
        behavior: prefersReducedMotion ? 'auto' : 'smooth',
    });
}

onMounted(() => {
    updateVisibility();

    window.addEventListener('scroll', updateVisibility, {
        passive: true,
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateVisibility);
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <button
            v-if="isVisible"
            type="button"
            aria-label="Scroll to top"
            title="Scroll to top"
            class="fixed right-6 bottom-6 z-50 inline-flex size-11 cursor-pointer items-center justify-center rounded-full border border-border bg-background text-foreground shadow-lg transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
            @click="scrollToTop"
        >
            <ArrowUp class="size-5" aria-hidden="true" />
        </button>
    </Transition>
</template>
