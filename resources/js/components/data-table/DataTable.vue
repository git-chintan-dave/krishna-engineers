<script lang="ts" setup>
import { computed } from 'vue';
import { FlexRender } from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

import AppSelect from '@/components/common/AppSelect.vue';
import { Button } from '@/components/ui/button';
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

type ColumnAlign = 'left' | 'center' | 'right';

type TableInstance = {
    getHeaderGroups: () => any[];
    getRowModel: () => { rows: any[] };
    getCanPreviousPage: () => boolean;
    getCanNextPage: () => boolean;
    getPageCount: () => number;
    previousPage: () => void;
    nextPage: () => void;
    setPageIndex: (pageIndex: number) => void;
    setPageSize: (pageSize: number) => void;
};

type PaginationItem =
    | { type: 'page'; page: number }
    | { type: 'ellipsis'; key: string };

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

const props = withDefaults(defineProps<Props>(), {
    selectedCount: 0,
    loading: false,
    pageSizeOptions: () => [10, 25, 50, 100],
    columnWidths: () => ({
        select: 'w-12',
        actions: 'w-24',
    }),
    emptyText: 'No records found.',
    itemLabel: 'records',
});

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

const showingFrom = computed(() =>
    props.total ? props.pageIndex * props.pageSize + 1 : 0,
);

const showingTo = computed(() =>
    Math.min((props.pageIndex + 1) * props.pageSize, props.total),
);

/*
|--------------------------------------------------------------------------
| Pagination Items
|--------------------------------------------------------------------------
*/

const paginationItems = computed<PaginationItem[]>(() => {
    const pageCount = props.table.getPageCount();
    const currentPage = props.pageIndex + 1;

    if (pageCount <= 7) {
        return Array.from({ length: pageCount }, (_, index) => ({
            type: 'page' as const,
            page: index + 1,
        }));
    }

    const items: PaginationItem[] = [];
    const addPage = (page: number) => items.push({ type: 'page', page });
    const addEllipsis = (key: string) => items.push({ type: 'ellipsis', key });

    addPage(1);

    if (currentPage > 4) {
        addEllipsis('start');
    }

    for (
        let page = Math.max(2, currentPage - 1);
        page <= Math.min(pageCount - 1, currentPage + 1);
        page++
    ) {
        addPage(page);
    }

    if (currentPage < pageCount - 3) {
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
    const headers = props.table.getHeaderGroups()[0]?.headers ?? [];

    return Array.from(
        { length: props.columnsCount },
        (_, index) => headers[index]?.column?.id ?? '',
    );
});

/*
|--------------------------------------------------------------------------
| Column Classes
|--------------------------------------------------------------------------
*/

const getColumnClass = (columnId: string) => props.columnWidths[columnId] ?? '';

const getColumnAlign = (column: any): ColumnAlign => {
    const align = column?.columnDef?.meta?.align;

    return align === 'center' || align === 'right' ? align : 'left';
};

const getAlignmentClass = (align: ColumnAlign) => ({
    'text-left': align === 'left',
    'text-center': align === 'center',
    'text-right': align === 'right',
});

const getPlaceholderAlignmentClass = (align: ColumnAlign) => ({
    'justify-start': align === 'left',
    'justify-center': align === 'center',
    'justify-end': align === 'right',
});

/*
|--------------------------------------------------------------------------
| Column Styles
|--------------------------------------------------------------------------
*/

const FIXED_COLUMN_IDS = ['select', 'status', 'created_at', 'actions'];

const getColumnStyle = (column: any) => {
    const columnId = column?.id;
    const columnDef = column?.columnDef;

    if (!columnDef) {
        return undefined;
    }

    const size = columnDef.size;

    if (FIXED_COLUMN_IDS.includes(columnId) && typeof size === 'number') {
        return {
            width: `${size}px`,
            minWidth: `${size}px`,
            maxWidth: `${size}px`,
        };
    }

    if (typeof size !== 'number') {
        return undefined;
    }

    return {
        width: `${size}px`,
        minWidth: `${columnDef.minSize ?? 20}px`,
        ...(typeof columnDef.maxSize === 'number'
            ? { maxWidth: `${columnDef.maxSize}px` }
            : {}),
    };
};

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handlePageSizeChange = (
    value: string | number | (string | number)[] | null,
) => {
    if (typeof value !== 'string' && typeof value !== 'number') {
        return;
    }

    const pageSize = Number(value);

    if (!Number.isFinite(pageSize) || pageSize <= 0) {
        return;
    }

    props.table.setPageSize(pageSize);
    props.table.setPageIndex(0);
};
</script>

<template>
    <div class="w-full min-w-0 overflow-hidden rounded-lg border bg-background">
        <!-- Table -->
        <div class="w-full min-w-0 overflow-x-auto overscroll-x-contain">
            <UiTable class="w-full table-fixed">
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                        class="bg-muted/30 hover:bg-muted/30"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            :class="[
                                getColumnClass(header.column.id),
                                getAlignmentClass(
                                    getColumnAlign(header.column),
                                ),
                            ]"
                            :style="getColumnStyle(header.column)"
                            class="h-11 px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground"
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :header="header"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <!-- Loading -->
                    <template v-if="loading">
                        <TableRow v-for="row in 5" :key="`loading-${row}`">
                            <TableCell
                                v-for="(columnId, index) in loadingColumnIds"
                                :key="index"
                                :class="getColumnClass(columnId)"
                                class="px-4 py-3"
                            >
                                <div
                                    :class="
                                        getPlaceholderAlignmentClass(
                                            getColumnAlign(
                                                table.getHeaderGroups()[0]
                                                    ?.headers[index]?.column,
                                            ),
                                        )
                                    "
                                    class="flex"
                                >
                                    <div
                                        class="h-4 w-3/4 max-w-32 animate-pulse rounded bg-muted"
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- Data -->
                    <template v-else-if="table.getRowModel().rows.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="
                                row.getIsSelected() ? 'selected' : undefined
                            "
                            class="group transition-colors hover:bg-muted/30 data-[state=selected]:bg-muted/50"
                        >
                            <TableCell
                                v-for="cell in row.getAllCells()"
                                :key="cell.id"
                                :class="[
                                    getColumnClass(cell.column.id),
                                    getAlignmentClass(
                                        getColumnAlign(cell.column),
                                    ),
                                ]"
                                :style="getColumnStyle(cell.column)"
                                class="px-4 py-3 align-middle"
                            >
                                <FlexRender :cell="cell" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- Empty -->
                    <TableRow v-else>
                        <TableCell
                            :colspan="columnsCount"
                            class="h-32 px-4 text-center"
                        >
                            <span class="text-sm text-muted-foreground">
                                {{ emptyText }}
                            </span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </UiTable>
        </div>

        <!-- Pagination -->
        <div class="border-t px-4 py-3 sm:px-5">
            <div class="flex flex-col gap-4">
                <!-- Summary -->
                <div class="min-w-0">
                    <p class="text-sm break-words text-muted-foreground">
                        Showing
                        <span class="font-medium text-foreground">
                            {{ showingFrom }}–{{ showingTo }}
                        </span>
                        of
                        <span class="font-medium text-foreground">
                            {{ total }}
                        </span>
                        {{ itemLabel }}

                        <template v-if="selectedCount">
                            <span class="mx-1.5">·</span>
                            <span class="font-medium text-foreground">
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
                    <div class="flex min-w-0 items-center gap-2">
                        <span
                            class="shrink-0 text-sm whitespace-nowrap text-muted-foreground"
                        >
                            Rows per page
                        </span>

                        <div class="w-24 shrink-0">
                            <AppSelect
                                :disabled="loading"
                                :model-value="pageSize"
                                :options="pageSizeOptions"
                                placeholder="10"
                                @update:model-value="handlePageSizeChange"
                            />
                        </div>
                    </div>

                    <!-- Pages -->
                    <div class="max-w-full min-w-0 overflow-x-auto pb-0.5">
                        <div
                            aria-label="Pagination"
                            class="flex w-max items-center gap-1"
                        >
                            <Button
                                :disabled="
                                    loading || !table.getCanPreviousPage()
                                "
                                aria-label="Previous page"
                                class="size-8 shrink-0 cursor-pointer"
                                size="icon"
                                variant="outline"
                                @click="table.previousPage()"
                            >
                                <ChevronLeft
                                    aria-hidden="true"
                                    class="size-4"
                                />
                            </Button>

                            <template
                                v-for="item in paginationItems"
                                :key="
                                    item.type === 'page' ? item.page : item.key
                                "
                            >
                                <Button
                                    v-if="item.type === 'page'"
                                    :aria-current="
                                        pageIndex === item.page - 1
                                            ? 'page'
                                            : undefined
                                    "
                                    :aria-label="`Page ${item.page}`"
                                    :disabled="loading"
                                    :variant="
                                        pageIndex === item.page - 1
                                            ? 'default'
                                            : 'ghost'
                                    "
                                    class="size-8 shrink-0 cursor-pointer"
                                    size="icon"
                                    @click="table.setPageIndex(item.page - 1)"
                                >
                                    {{ item.page }}
                                </Button>

                                <span
                                    v-else
                                    aria-hidden="true"
                                    class="flex size-8 shrink-0 items-center justify-center text-sm text-muted-foreground"
                                >
                                    …
                                </span>
                            </template>

                            <Button
                                :disabled="loading || !table.getCanNextPage()"
                                aria-label="Next page"
                                class="size-8 shrink-0 cursor-pointer"
                                size="icon"
                                variant="outline"
                                @click="table.nextPage()"
                            >
                                <ChevronRight
                                    aria-hidden="true"
                                    class="size-4"
                                />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
