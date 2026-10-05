<script setup lang="ts">
import {computed} from 'vue';
import {FlexRender} from '@tanstack/vue-table';
import {
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';

import AppSelect from '@/components/common/AppSelect.vue';
import {Button} from '@/components/ui/button';
import {
    Table as UiTable,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type TableInstance = {
    getHeaderGroups: () => any[];
    getRowModel: () => {
        rows: any[];
    };

    getCanPreviousPage: () => boolean;
    getCanNextPage: () => boolean;
    getPageCount: () => number;

    previousPage: () => void;
    nextPage: () => void;

    setPageIndex: (pageIndex: number) => void;
    setPageSize: (pageSize: number) => void;
};

type PaginationItem =
    | {
    type: 'page';
    page: number;
}
    | {
    type: 'ellipsis';
    key: string;
};

type Props = {
    table: TableInstance;
    columnsCount: number;

    total: number;
    pageIndex: number;
    pageSize: number;

    selectedCount?: number;
    loading?: boolean;

    pageSizeOptions?: number[];
    columnWidths?: Record<string, string>;

    emptyText?: string;
    itemLabel?: string;
};

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<Props>(),
    {
        selectedCount: 0,
        loading: false,

        pageSizeOptions: () => [
            10,
            25,
            50,
            100,
        ],

        columnWidths: () => ({
            select: 'w-12',
            actions: 'w-24',
        }),

        emptyText: 'No records found.',
        itemLabel: 'records',
    },
);

/*
|--------------------------------------------------------------------------
| Pagination Options
|--------------------------------------------------------------------------
*/

const pageSizeOptions = computed(() =>
    props.pageSizeOptions.map((size) => ({
        value: size,
        label: String(size),
    })),
);

/*
|--------------------------------------------------------------------------
| Pagination Summary
|--------------------------------------------------------------------------
*/

const showingFrom = computed(() => {
    if (!props.total) {
        return 0;
    }

    return (
        props.pageIndex *
        props.pageSize +
        1
    );
});

const showingTo = computed(() => {
    if (!props.total) {
        return 0;
    }

    return Math.min(
        (props.pageIndex + 1) *
        props.pageSize,
        props.total,
    );
});

/*
|--------------------------------------------------------------------------
| Pagination Items
|--------------------------------------------------------------------------
*/

const paginationItems = computed<
    PaginationItem[]
>(() => {
    const pageCount =
        props.table.getPageCount();

    const currentPage =
        props.pageIndex + 1;

    if (pageCount <= 7) {
        return Array.from(
            {length: pageCount},
            (_, index) => ({
                type: 'page' as const,
                page: index + 1,
            }),
        );
    }

    const items: PaginationItem[] = [];

    const addPage = (page: number) => {
        items.push({
            type: 'page',
            page,
        });
    };

    const addEllipsis = (key: string) => {
        items.push({
            type: 'ellipsis',
            key,
        });
    };

    addPage(1);

    if (currentPage > 4) {
        addEllipsis('start');
    }

    const startPage = Math.max(
        2,
        currentPage - 1,
    );

    const endPage = Math.min(
        pageCount - 1,
        currentPage + 1,
    );

    for (
        let page = startPage;
        page <= endPage;
        page++
    ) {
        addPage(page);
    }

    if (
        currentPage <
        pageCount - 3
    ) {
        addEllipsis('end');
    }

    addPage(pageCount);

    return items;
});

/*
|--------------------------------------------------------------------------
| Loading Columns
|--------------------------------------------------------------------------
*/

const loadingColumnIds = computed(() => {
    const firstHeaderGroup =
        props.table.getHeaderGroups()[0];

    return Array.from(
        {length: props.columnsCount},
        (_, index) =>
            firstHeaderGroup
                ?.headers[index]
                ?.column?.id ?? '',
    );
});

/*
|--------------------------------------------------------------------------
| Column Widths
|--------------------------------------------------------------------------
*/

const getColumnClass = (
    columnId: string,
) => {
    return (
        props.columnWidths[columnId] ?? ''
    );
};

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handlePageSizeChange = (
    value:
        | string
        | number
        | (string | number)[]
        | null,
) => {
    if (
        typeof value !== 'string' &&
        typeof value !== 'number'
    ) {
        return;
    }

    const pageSize = Number(value);

    if (
        !Number.isFinite(pageSize) ||
        pageSize <= 0
    ) {
        return;
    }

    props.table.setPageSize(pageSize);
    props.table.setPageIndex(0);
};
</script>

<template>
    <div
        class="w-full min-w-0 overflow-hidden rounded-lg border bg-background"
    >
        <!-- Table -->
        <div
            class="w-full min-w-0 overflow-x-auto overscroll-x-contain"
        >
            <UiTable
                class="w-full min-w-[max-content] table-fixed"
            >
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                        class="bg-muted/30 hover:bg-muted/30"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            class="h-11 px-4 text-xs font-medium tracking-wide text-muted-foreground"
                            :class="
                                getColumnClass(
                                    header.column.id,
                                )
                            "
                        >
                            <FlexRender
                                v-if="
                                    !header.isPlaceholder
                                "
                                :header="header"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <!-- Loading -->
                    <template v-if="loading">
                        <TableRow
                            v-for="row in 5"
                            :key="`loading-${row}`"
                        >
                            <TableCell
                                v-for="(
                                    columnId,
                                    index
                                ) in loadingColumnIds"
                                :key="index"
                                class="px-4 py-3"
                                :class="
                                    getColumnClass(
                                        columnId,
                                    )
                                "
                            >
                                <div
                                    class="h-4 animate-pulse rounded bg-muted"
                                />
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- Data -->
                    <template
                        v-else-if="
                            table
                                .getRowModel()
                                .rows.length
                        "
                    >
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            class="group transition-colors hover:bg-muted/30 data-[state=selected]:bg-muted/50"
                            :data-state="
                                row.getIsSelected()
                                    ? 'selected'
                                    : undefined
                            "
                        >
                            <TableCell
                                v-for="cell in row.getAllCells()"
                                :key="cell.id"
                                class="px-4 py-3 align-middle"
                                :class="
                                    getColumnClass(
                                        cell.column.id,
                                    )
                                "
                            >
                                <FlexRender
                                    :cell="cell"
                                />
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- Empty -->
                    <TableRow v-else>
                        <TableCell
                            :colspan="columnsCount"
                            class="h-32 px-4 text-center"
                        >
                            <span
                                class="text-sm text-muted-foreground"
                            >
                                {{ emptyText }}
                            </span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </UiTable>
        </div>

        <!-- Pagination -->
        <div
            class="border-t px-4 py-3 sm:px-5"
        >
            <div class="flex flex-col gap-4">
                <!-- Summary -->
                <div class="min-w-0">
                    <p
                        class="break-words text-sm text-muted-foreground"
                    >
                        Showing
                        <span
                            class="font-medium text-foreground"
                        >
                            {{ showingFrom }}–{{ showingTo }}
                        </span>
                        of
                        <span
                            class="font-medium text-foreground"
                        >
                            {{ total }}
                        </span>
                        {{ itemLabel }}

                        <template
                            v-if="selectedCount"
                        >
                            <span class="mx-1.5">
                                ·
                            </span>

                            <span
                                class="font-medium text-foreground"
                            >
                                {{ selectedCount }}
                            </span>

                            selected
                        </template>
                    </p>
                </div>

                <!-- Pagination Controls -->
                <div
                    class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <!-- Page Size -->
                    <div
                        class="flex min-w-0 items-center gap-2"
                    >
                        <span
                            class="shrink-0 text-sm whitespace-nowrap text-muted-foreground"
                        >
                            Rows per page
                        </span>

                        <div class="w-24 shrink-0">
                            <AppSelect
                                :model-value="pageSize"
                                :options="pageSizeOptions"
                                placeholder="10"
                                :disabled="loading"
                                @update:model-value="
                                    handlePageSizeChange
                                "
                            />
                        </div>
                    </div>

                    <!-- Pages -->
                    <div
                        class="min-w-0 max-w-full overflow-x-auto pb-0.5"
                    >
                        <div
                            class="flex w-max items-center gap-1"
                            aria-label="Pagination"
                        >
                            <!-- Previous -->
                            <Button
                                variant="outline"
                                size="icon"
                                class="size-8 shrink-0 cursor-pointer"
                                :disabled="
                                    loading ||
                                    !table.getCanPreviousPage()
                                "
                                @click="
                                    table.previousPage()
                                "
                            >
                                <ChevronLeft
                                    class="size-4"
                                    aria-hidden="true"
                                />

                                <span class="sr-only">
                                    Previous page
                                </span>
                            </Button>

                            <!-- Page Numbers -->
                            <template
                                v-for="item in paginationItems"
                                :key="
                                    item.type === 'page'
                                        ? item.page
                                        : item.key
                                "
                            >
                                <Button
                                    v-if="
                                        item.type ===
                                        'page'
                                    "
                                    :variant="
                                        pageIndex ===
                                        item.page - 1
                                            ? 'default'
                                            : 'ghost'
                                    "
                                    size="icon"
                                    class="size-8 shrink-0 cursor-pointer"
                                    :disabled="loading"
                                    @click="
                                        table.setPageIndex(
                                            item.page - 1,
                                        )
                                    "
                                >
                                    {{ item.page }}
                                </Button>

                                <span
                                    v-else
                                    class="flex size-8 shrink-0 items-center justify-center text-sm text-muted-foreground"
                                    aria-hidden="true"
                                >
                                    …
                                </span>
                            </template>

                            <!-- Next -->
                            <Button
                                variant="outline"
                                size="icon"
                                class="size-8 shrink-0 cursor-pointer"
                                :disabled="
                                    loading ||
                                    !table.getCanNextPage()
                                "
                                @click="
                                    table.nextPage()
                                "
                            >
                                <ChevronRight
                                    class="size-4"
                                    aria-hidden="true"
                                />

                                <span class="sr-only">
                                    Next page
                                </span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
