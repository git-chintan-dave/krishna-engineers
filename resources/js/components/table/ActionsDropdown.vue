<script lang="ts" setup>
import {MoreHorizontal} from '@lucide/vue';
import type {Component} from 'vue';

import {Button} from '@/components/ui/button';
import {DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,} from '@/components/ui/dropdown-menu';
import {executeConfirm} from '@/directives/confirm';

type ConfirmationOptions = {
    title: string;
    description: string;
    confirmText: string;
};

type DropdownAction = {
    key: string;
    label: string;
    icon: Component;
    confirmation: ConfirmationOptions;
    action: () => void | Promise<void>;
};

const props = defineProps<{
    name: string;
    actions: DropdownAction[];
}>();

const handleAction = (item: DropdownAction) => {
    void executeConfirm({
        action: item.action,
        options: item.confirmation,
    });
};
</script>

<template>
    <DropdownMenu v-if="actions.length > 0">
        <DropdownMenuTrigger as-child>
            <Button
                :aria-label="`More actions for ${name}`"
                class="size-8 shrink-0 cursor-pointer text-muted-foreground hover:bg-muted hover:text-foreground"
                size="icon"
                type="button"
                variant="ghost"
                @click.stop
            >
                <MoreHorizontal
                    aria-hidden="true"
                    class="size-4"
                />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            class="min-w-44"
            @click.stop
        >
            <DropdownMenuItem
                v-for="item in actions"
                :key="item.key"
                class="cursor-pointer gap-2"
                @select="handleAction(item)"
            >
                <component
                    :is="item.icon"
                    aria-hidden="true"
                    class="size-4 shrink-0"
                />

                <span>{{ item.label }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
