<script lang="ts" setup>
/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

withDefaults(
    defineProps<{
        label?: string;
        description?: string;
        required?: boolean;
        inputId?: string;
    }>(),
    {
        label: '',
        description: '',
        required: false,
        inputId: undefined,
    },
);
</script>

<template>
    <div class="w-full min-w-0 space-y-1.5">
        <!--
        |--------------------------------------------------------------------------
        | Label
        |--------------------------------------------------------------------------
        -->

        <label
            v-if="label"
            :for="inputId"
            class="text-sm leading-none font-medium text-foreground"
        >
            {{ label }}

            <span
                v-if="required"
                class="ml-0.5 text-destructive"
            >
                <span aria-hidden="true">*</span>
                <span class="sr-only"> (required)</span>
            </span>
        </label>

        <!--
        |--------------------------------------------------------------------------
        | Field
        |--------------------------------------------------------------------------
        -->

        <slot />

        <!--
        |--------------------------------------------------------------------------
        | Description
        |--------------------------------------------------------------------------
        -->

        <p v-if="description" class="text-xs text-muted-foreground">
            {{ description }}
        </p>
    </div>
</template>
