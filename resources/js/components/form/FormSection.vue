<script lang="ts" setup>
withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: '',
        description: '',
    },
);
</script>

<template>
    <section class="w-full min-w-0 rounded-lg border bg-background">
        <div v-if="title || description" class="mb-4 space-y-1 px-4 py-4 md:px-6 border-b">
            <h2
                v-if="title"
                class="text-sm font-semibold tracking-tight text-foreground"
            >
                {{ title }}
            </h2>

            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <div class="w-full min-w-0 p-4 md:p-6">
            <slot />
        </div>
    </section>
</template>
