<script setup lang="ts">
import { Link, type Href } from '@inertiajs/vue3';
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
            type="button"
            variant="destructive"
            size="sm"
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
            :aria-label="`Delete ${selectedCount} selected ${resourceLabel}`"
        >
            <Trash2 class="size-4" aria-hidden="true" />

            <span class="whitespace-nowrap">
                Delete selected

                <span class="ml-1 opacity-80" aria-hidden="true">
                    ({{ selectedCount }})
                </span>
            </span>
        </Button>

        <!-- Import -->
        <Button
            v-if="importUrl"
            as-child
            variant="outline"
            size="sm"
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
        >
            <Link :href="importUrl" :aria-label="importLabel">
                <FileUp class="size-4" aria-hidden="true" />

                <span class="whitespace-nowrap">
                    {{ importLabel }}
                </span>
            </Link>
        </Button>

        <!-- Export -->
        <Button
            v-if="exportUrl"
            as-child
            variant="outline"
            size="sm"
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
        >
            <Link :href="exportUrl" :aria-label="exportLabel">
                <Download class="size-4" aria-hidden="true" />

                <span class="whitespace-nowrap">
                    {{ exportLabel }}
                </span>
            </Link>
        </Button>

        <!-- Generate -->
        <Button
            v-if="generateUrl"
            as-child
            variant="outline"
            size="sm"
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
            :disabled="generateDisabled"
        >
            <Link
                :href="generateUrl"
                method="post"
                :aria-label="generateLabel"
                :aria-disabled="generateDisabled"
                :tabindex="generateDisabled ? -1 : undefined"
            >
                <Banknote class="size-4" aria-hidden="true" />

                <span class="whitespace-nowrap">
                    {{ generateLabel }}
                </span>
            </Link>
        </Button>

        <!-- Create -->
        <Button
            v-if="createUrl"
            as-child
            size="sm"
            class="h-9 w-full shrink-0 cursor-pointer sm:w-auto"
        >
            <Link :href="createUrl" :aria-label="createLabel">
                <Plus class="size-4" aria-hidden="true" />

                <span class="whitespace-nowrap">
                    {{ createLabel }}
                </span>
            </Link>
        </Button>
    </div>
</template>
