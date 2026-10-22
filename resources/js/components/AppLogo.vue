<script lang="ts" setup>
import {usePage} from '@inertiajs/vue3';

const page = usePage<{
    name: string;
}>();
</script>

<template>
    <div class="flex min-w-0 items-center gap-2.5">
        <img
            alt="Logo"
            class="size-8 shrink-0 object-contain"
            src="/logo_new.svg"
        />

        <span
            class="truncate text-base leading-tight font-semibold"
        >
            {{ page.props.name }}
        </span>
    </div>
</template>
