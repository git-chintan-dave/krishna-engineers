<script lang="ts" setup>
import { type Href, Link } from '@inertiajs/vue3';
import { Banknote, Download, FileUp, Plus, Trash2 } from '@lucide/vue';

import vConfirm from '@/directives/confirm';
import { Button } from '@/components/ui/button';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

withDefaults(
    defineProps<{
        createUrl?: Href;
        createLabel?: string;

        importUrl?: Href;
        importLabel?: string;

        exportUrl?: Href;
        exportLabel?: string;

        generateUrl?: Href;
        generateLabel?: string;
        generateDisabled?: boolean;

        selectedCount?: number;
        resourceLabel?: string;

        deleteTitle?: string;
        deleteDescription?: string;
        deleteConfirmLabel?: string;
    }>(),
    {
        createLabel: 'Add',

        importLabel: 'Import',
        exportLabel: 'Export',

        generateLabel: 'Generate',
        generateDisabled: false,

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
</script>

<template>
    <div
        class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center"
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
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
            size="sm"
            type="button"
            variant="destructive"
        >
            <Trash2 aria-hidden="true" class="size-4" />

            <span class="whitespace-nowrap">
                Delete selected

                <span aria-hidden="true" class="ml-1 opacity-80">
                    ({{ selectedCount }})
                </span>
            </span>
        </Button>

        <!-- Import -->
        <Button
            v-if="importUrl"
            as-child
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
            size="sm"
            variant="outline"
        >
            <Link :aria-label="importLabel" :href="importUrl">
                <FileUp aria-hidden="true" class="size-4" />

                <span class="whitespace-nowrap">
                    {{ importLabel }}
                </span>
            </Link>
        </Button>

        <!-- Export -->
        <Button
            v-if="exportUrl"
            as-child
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
            size="sm"
            variant="outline"
        >
            <Link :aria-label="exportLabel" :href="exportUrl">
                <Download aria-hidden="true" class="size-4" />

                <span class="whitespace-nowrap">
                    {{ exportLabel }}
                </span>
            </Link>
        </Button>

        <!-- Generate -->
        <Button
            v-if="generateUrl"
            :disabled="generateDisabled"
            as-child
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
            size="sm"
            variant="outline"
        >
            <Link
                :aria-disabled="generateDisabled"
                :aria-label="generateLabel"
                :href="generateUrl"
                :tabindex="generateDisabled ? -1 : undefined"
                method="post"
            >
                <Banknote aria-hidden="true" class="size-4" />

                <span class="whitespace-nowrap">
                    {{ generateLabel }}
                </span>
            </Link>
        </Button>

        <!-- Sync -->
        <slot name="sync" />

        <!-- Create -->
        <Button
            v-if="createUrl"
            as-child
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
            size="sm"
        >
            <Link :aria-label="createLabel" :href="createUrl">
                <Plus aria-hidden="true" class="size-4" />

                <span class="whitespace-nowrap">
                    {{ createLabel }}
                </span>
            </Link>
        </Button>
    </div>
</template>
