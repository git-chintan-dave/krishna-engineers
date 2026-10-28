<script lang="ts" setup>
import type {DropdownMenuLabelProps} from 'reka-ui';
import {DropdownMenuLabel, useForwardProps} from 'reka-ui';
import {reactiveOmit} from '@vueuse/core';
import type {HTMLAttributes} from 'vue';

import {cn} from '@/lib/utils';

const props = defineProps<
    DropdownMenuLabelProps & {
    class?: HTMLAttributes['class'];
    inset?: boolean;
}
>();

const delegatedProps = reactiveOmit(props, 'class', 'inset');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <DropdownMenuLabel
        v-bind="forwardedProps"
        :class="
            cn(
                'flex items-center gap-2 px-2 py-1.5 text-sm font-semibold data-[inset]:pl-8',
                props.class,
            )
        "
        :data-inset="inset ? '' : undefined"
        data-slot="dropdown-menu-label"
    >
        <slot />
    </DropdownMenuLabel>
</template>
