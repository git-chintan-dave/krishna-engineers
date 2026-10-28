<script lang="ts" setup>
import type {CheckboxRootEmits, CheckboxRootProps} from 'reka-ui';
import {CheckboxIndicator, CheckboxRoot, useForwardPropsEmits,} from 'reka-ui';
import type {HTMLAttributes} from 'vue';
import {Check, Minus} from '@lucide/vue';
import {reactiveOmit} from '@vueuse/core';
import {cn} from '@/lib/utils';

const props = defineProps<
    CheckboxRootProps & { class?: HTMLAttributes['class'] }
>();

const emits = defineEmits<CheckboxRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <CheckboxRoot
        v-slot="slotProps"
        :class="
            cn(
                'peer size-4 shrink-0 rounded-[4px] border border-input shadow-xs transition-shadow outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=indeterminate]:border-primary data-[state=indeterminate]:bg-primary data-[state=indeterminate]:text-primary-foreground',
                props.class,
            )
        "
        data-slot="checkbox"
        v-bind="forwarded"
    >
        <CheckboxIndicator
            class="grid place-content-center text-current transition-none"
            data-slot="checkbox-indicator"
        >
            <slot v-bind="slotProps">
                <Minus
                    v-if="slotProps.state === 'indeterminate'"
                    class="size-3.5"
                />
                <Check v-else class="size-3.5" />
            </slot>
        </CheckboxIndicator>
    </CheckboxRoot>
</template>
