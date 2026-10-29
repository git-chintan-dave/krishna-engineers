<script lang="ts" setup>
import type {HTMLAttributes} from "vue"
import {cn} from "@/lib/utils"

const props = defineProps<{
    class?: HTMLAttributes["class"]
}>()
</script>

<template>
    <table
        :class="cn('w-full caption-bottom text-sm', props.class)"
        data-slot="table"
    >
        <slot/>
    </table>
</template>
