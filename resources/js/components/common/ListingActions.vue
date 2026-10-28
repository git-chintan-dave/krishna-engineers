
<script lang="ts" setup>
import {Link, router} from '@inertiajs/vue3';
import {EllipsisVertical, Plus, Trash2} from '@lucide/vue';
import type {Component} from 'vue';

import vConfirm, {executeConfirm,} from '@/directives/confirm';

import {Button} from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import {ACTION_SHORTCUTS, useActionShortcuts,} from '@/composables/useActionShortcuts';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ActionBase = {
    key: string;
    label: string;
    icon: Component;
    shortcut?: string;
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

const props = withDefaults(
    defineProps<{
        createUrl?: string;
        createLabel?: string;
        actions?: ActionItem[];
        actionsLabel?: string;
        selectedCount?: number;
        resourceLabel?: string;
        deleteTitle?: string;
        deleteDescription?: string;
        deleteConfirmLabel?: string;
    }>(),
    {
        createLabel: 'Add',
        actions: () => [],
        actionsLabel: 'Actions',
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

const handleAdd = (): void => {
    if (props.createUrl) {
        router.visit(props.createUrl);
    }
};

const handleDeleteSelected = (): void => {
    if (props.selectedCount > 0) {
        emit('deleteSelected');
    }
};

const triggerDeleteSelected = async (): Promise<void> => {
    if (props.selectedCount <= 0) {
        return;
    }

    await executeConfirm({
        action: handleDeleteSelected,
        options: {
            title:
                props.deleteTitle ||
                `Delete selected ${props.resourceLabel}?`,
            description: props.deleteDescription,
            confirmText: props.deleteConfirmLabel,
        },
    });
};

/*
|--------------------------------------------------------------------------
| Keyboard Shortcuts
|--------------------------------------------------------------------------
|
| Add and bulk delete are handled by this component.
| Page-specific shortcuts are registered by the parent listing page.
|
*/

useActionShortcuts(() => ({
    ...(props.createUrl
        ? {
            add: {
                handler: handleAdd,
            },
        }
        : {}),

    bulkDelete: {
        handler: triggerDeleteSelected,
        enabled: () => props.selectedCount > 0,
    },
}));
</script>

<template>
    <div
        class="flex w-full min-w-0 items-center gap-2 sm:w-auto sm:justify-end"
    >
        <!-- Delete Selected -->
        <Button
            v-if="selectedCount > 0"
            v-confirm="{
                action: handleDeleteSelected,
                options: {
                    title:
                        deleteTitle ||
                        `Delete selected ${resourceLabel}?`,
                    description: deleteDescription,
                    confirmText: deleteConfirmLabel,
                },
            }"
            :aria-label="`Delete ${selectedCount} selected ${resourceLabel}`"
            class="h-9 min-w-0 flex-1 cursor-pointer gap-1.5 px-3 sm:flex-none"
            size="sm"
            type="button"
            variant="destructive"
        >
            <Trash2
                aria-hidden="true"
                class="size-4 shrink-0"
            />

            <span class="truncate">
                <span class="sm:hidden">
                    Delete ({{ selectedCount }})
                </span>

                <span class="hidden sm:inline">
                    Delete selected ({{ selectedCount }})
                </span>
            </span>

            <kbd
                class="ml-1 hidden shrink-0 font-mono text-[10px] font-normal leading-none text-current/80 md:inline"
                aria-hidden="true"
            >
                {{ ACTION_SHORTCUTS.bulkDelete.label }}
            </kbd>
        </Button>

        <!-- Add -->
        <Button
            v-if="createUrl"
            as-child
            class="h-9 min-w-0 flex-1 cursor-pointer gap-1.5 px-3 sm:flex-none"
            size="sm"
        >
            <Link
                :aria-label="createLabel"
                :href="createUrl"
            >
                <Plus
                    aria-hidden="true"
                    class="size-4 shrink-0"
                />

                <span class="truncate">
                    {{ createLabel }}
                </span>

                <kbd
                    class="ml-1 hidden shrink-0 font-mono text-[10px] font-normal leading-none text-current/70 md:inline"
                    aria-hidden="true"
                >
                    {{ ACTION_SHORTCUTS.add.label }}
                </kbd>
            </Link>
        </Button>

        <!-- More Actions -->
        <DropdownMenu v-if="actions.length > 0">
            <DropdownMenuTrigger as-child>
                <Button
                    :aria-label="actionsLabel || 'More actions'"
                    class="size-9 shrink-0 cursor-pointer"
                    size="icon"
                    type="button"
                    variant="outline"
                >
                    <EllipsisVertical
                        aria-hidden="true"
                        class="size-4"
                    />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                class="max-h-[min(70vh,32rem)] w-64 max-w-[calc(100vw-2rem)] overflow-y-auto"
            >
                <DropdownMenuLabel v-if="actionsLabel">
                    {{ actionsLabel }}
                </DropdownMenuLabel>

                <template
                    v-for="(item, index) in actions"
                    :key="item.key"
                >
                    <DropdownMenuSeparator
                        v-if="index > 0 && item.separatorBefore"
                    />

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
                            class="flex w-full min-w-0 items-center gap-2"
                        >
                            <component
                                :is="item.icon"
                                aria-hidden="true"
                                class="size-4 shrink-0"
                            />

                            <span class="min-w-0 flex-1 truncate">
                                {{ item.label }}
                            </span>

                            <DropdownMenuShortcut
                                v-if="item.shortcut"
                                class="font-mono text-[10px] tracking-normal"
                            >
                                {{ item.shortcut }}
                            </DropdownMenuShortcut>
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

                        <span class="min-w-0 flex-1 truncate">
                            {{ item.label }}
                        </span>

                        <DropdownMenuShortcut
                            v-if="item.shortcut"
                            class="font-mono text-[10px] tracking-normal"
                        >
                            {{ item.shortcut }}
                        </DropdownMenuShortcut>
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
