<script lang="ts" setup>
import {Link} from '@inertiajs/vue3';
import {EllipsisVertical, Plus, Trash2} from '@lucide/vue';
import type {Component} from 'vue';

import vConfirm from '@/directives/confirm';
import {Button} from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ActionBase = {
    key: string;
    label: string;
    icon: Component;
    disabled?: boolean;
    variant?: 'default' | 'destructive';
    separatorBefore?: boolean;
};

export type ActionItem =
    | (ActionBase & {
    href: string;
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    action?: never;
})
    | (ActionBase & {
    action: () => void;
    href?: never;
    method?: never;
});

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

withDefaults(
    defineProps<{
        createUrl?: string;
        createLabel?: string;

        actions?: ActionItem[];

        selectedCount?: number;
        resourceLabel?: string;

        deleteTitle?: string;
        deleteDescription?: string;
        deleteConfirmLabel?: string;
    }>(),
    {
        createLabel: 'Add',
        actions: () => [],
        selectedCount: 0,
        resourceLabel: 'items',

        deleteTitle: '',
        deleteDescription:
            'This action cannot be undone. The selected records will be permanently deleted.',
        deleteConfirmLabel: 'Delete',
    },
);

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    deleteSelected: [];
}>();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getActionClass = (variant?: ActionItem['variant']) => ({
    'text-destructive focus:text-destructive':
        variant === 'destructive',
});
</script>

<template>
    <div
        class="flex w-full min-w-0 items-center gap-2 sm:w-auto sm:justify-end"
    >
        <!-- Delete Selected -->
        <Button
            v-if="selectedCount > 0"
            v-confirm="{
                action: () => emit('deleteSelected'),
                options: {
                    title: deleteTitle || `Delete selected ${resourceLabel}?`,
                    description: deleteDescription,
                    confirmText: deleteConfirmLabel,
                },
            }"
            :aria-label="`Delete ${selectedCount} selected ${resourceLabel}`"
            class="h-9 min-w-0 flex-1 cursor-pointer px-2.5 sm:flex-none sm:px-3"
            size="sm"
            type="button"
            variant="destructive"
        >
            <Trash2 aria-hidden="true" class="size-4 shrink-0" />

            <span class="truncate">
                <span class="sm:hidden">
                    Delete ({{ selectedCount }})
                </span>

                <span class="hidden sm:inline">
                    Delete selected ({{ selectedCount }})
                </span>
            </span>
        </Button>

        <!-- Add -->
        <Button
            v-if="createUrl"
            as-child
            class="h-9 min-w-0 flex-1 cursor-pointer px-3 sm:flex-none"
            size="sm"
        >
            <Link :aria-label="createLabel" :href="createUrl">
                <Plus aria-hidden="true" class="size-4 shrink-0" />
                <span class="truncate">{{ createLabel }}</span>
            </Link>
        </Button>

        <!-- More Actions -->
        <DropdownMenu v-if="actions.length > 0">
            <DropdownMenuTrigger as-child>
                <Button
                    aria-label="More actions"
                    class="size-9 shrink-0 cursor-pointer"
                    size="icon"
                    type="button"
                    variant="outline"
                >
                    <EllipsisVertical aria-hidden="true" class="size-4" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                class="max-h-[min(70vh,32rem)] w-60 max-w-[calc(100vw-2rem)] overflow-y-auto"
            >
                <template
                    v-for="item in actions"
                    :key="item.key"
                >
                    <DropdownMenuSeparator v-if="item.separatorBefore" />

                    <!-- Navigation Action -->
                    <DropdownMenuItem
                        v-if="'href' in item"
                        as-child
                        :disabled="item.disabled"
                        :class="[
                            'cursor-pointer',
                            getActionClass(item.variant),
                        ]"
                    >
                        <Link
                            :href="item.href"
                            :method="item.method ?? 'get'"
                            class="flex w-full items-center gap-2"
                        >
                            <component
                                :is="item.icon"
                                aria-hidden="true"
                                class="size-4 shrink-0"
                            />

                            <span class="truncate">{{ item.label }}</span>
                        </Link>
                    </DropdownMenuItem>

                    <!-- Callback Action -->
                    <DropdownMenuItem
                        v-else
                        :disabled="item.disabled"
                        :class="[
                            'cursor-pointer',
                            getActionClass(item.variant),
                        ]"
                        @select="item.action()"
                    >
                        <component
                            :is="item.icon"
                            aria-hidden="true"
                            class="size-4 shrink-0"
                        />

                        <span class="truncate">{{ item.label }}</span>
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
