<script lang="ts" setup>
import {type Component, computed} from 'vue';
import {FlexRender} from '@tanstack/vue-table';
import {ChevronLeft, ChevronRight} from '@lucide/vue';

import AppSelect from '@/components/common/AppSelect.vue';
import {Button} from '@/components/ui/button';
import {Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle,} from '@/components/ui/empty';
import {Table as UiTable, TableBody, TableCell, TableHead, TableHeader, TableRow,} from '@/components/ui/table';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ColumnAlign = 'left' | 'center' | 'right';

type RowHighlight =
    | 'danger'
    | 'warning'
    | 'success'
    | 'info'
    | 'primary'
    | 'muted';

type RowHighlightResolver<T> = (
    row: T,
) => RowHighlight | undefined;

type EmptyStateConfig = {
    title: string;
    description?: string;
    actionLabel?: string;
    onAction?: () => void;
    icon?: Component;
};

type PaginationItem =
    | { type: 'page'; page: number }
    | { type: 'ellipsis'; key: string };

type Props = {
    table: any;
    columnsCount: number;
    total: number;
    pageIndex: number;
    pageSize: number;
    selectedCount?: number;
    loading?: boolean;
    pageSizeOptions?: number[];
    columnWidths?: Record<string, string>;
    emptyText?: string;
    emptyState?: EmptyStateConfig;
    itemLabel?: string;
    rowHighlight?: RowHighlightResolver<any>;
};

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const SELECT_COLUMN_ID = 'select';
const ACTIONS_COLUMN_ID = 'actions';

const INTERACTIVE_SELECTOR = [
    'button',
    'a',
    'input',
    'select',
    'textarea',
    'summary',
    '[role="button"]',
    '[role="checkbox"]',
    '[role="menuitem"]',
    '[data-row-selection-ignore]',
].join(', ');

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
| Row Selection
|--------------------------------------------------------------------------
*/

const hasSelectionColumn = computed(() =>
    props.table
        .getAllLeafColumns()
        .some((column: any) => column.id === SELECT_COLUMN_ID),
);

const handleRowClick = (event: MouseEvent, row: any): void => {
    if (
        !hasSelectionColumn.value ||
        !row.getCanSelect() ||
        props.loading
    ) {
        return;
    }

    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    if (
        target.closest(
            `[data-column-id="${ACTIONS_COLUMN_ID}"]`,
        )
    ) {
        return;
    }

    if (target.closest(INTERACTIVE_SELECTOR)) {
        return;
    }

    row.toggleSelected();
};

/*
|--------------------------------------------------------------------------
| Table Width
|--------------------------------------------------------------------------
*/

const getColumnMinimumWidth = (column: any): number => {
    const size = column.getSize?.() ?? column.columnDef?.size;
    const minSize = column.columnDef?.minSize;

    if (typeof size === 'number') {
        return Math.max(size, minSize ?? 0);
    }

    return minSize ?? 150;
};

const tableMinWidth = computed(() =>
    props.table
        .getAllLeafColumns()
        .reduce(
            (total: number, column: any) =>
                total + getColumnMinimumWidth(column),
            0,
        ),
);

const tableStyle = computed(() => ({
    width: `max(100%, ${tableMinWidth.value}px)`,
}));

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
    props.total > 0
        ? props.pageIndex * props.pageSize + 1
        : 0,
);

const showingTo = computed(() =>
    Math.min(
        (props.pageIndex + 1) * props.pageSize,
        props.total,
    ),
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

    const addPage = (page: number): void => {
        items.push({ type: 'page', page });
    };

    const addEllipsis = (key: string): void => {
        items.push({ type: 'ellipsis', key });
    };

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

const visibleColumns = computed(() => {
    const table = props.table;

    if (typeof table?.getVisibleLeafColumns === 'function') {
        return table.getAllLeafColumns();
    }

    return table?.getAllLeafColumns?.() ?? [];
});

const loadingColumns = computed(() => visibleColumns.value);

const loadingRowCount = computed(() =>
    Math.min(Math.max(props.pageSize || 10, 5), 10),
);

/*
|--------------------------------------------------------------------------
| Column Classes and Alignment
|--------------------------------------------------------------------------
*/

const getColumnClass = (columnId: string): string =>
    props.columnWidths[columnId] ?? '';

const getColumnAlign = (column?: any): ColumnAlign => {
    const align = column?.columnDef?.meta?.align;

    return align === 'center' || align === 'right'
        ? align
        : 'left';
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
| Row Highlight
|--------------------------------------------------------------------------
| Subtle colors with light/dark variants.
*/

const ROW_HIGHLIGHT_CLASSES: Record<RowHighlight, string> = {
    danger: [
        'bg-red-500/[0.045]',
        'hover:bg-red-500/[0.075]',
        'dark:bg-red-400/[0.07]',
        'dark:hover:bg-red-400/[0.11]',
    ].join(' '),

    warning: [
        'bg-amber-500/[0.055]',
        'hover:bg-amber-500/[0.085]',
        'dark:bg-amber-400/[0.07]',
        'dark:hover:bg-amber-400/[0.11]',
    ].join(' '),

    success: [
        'bg-emerald-500/[0.045]',
        'hover:bg-emerald-500/[0.075]',
        'dark:bg-emerald-400/[0.07]',
        'dark:hover:bg-emerald-400/[0.11]',
    ].join(' '),

    info: [
        'bg-sky-500/[0.045]',
        'hover:bg-sky-500/[0.075]',
        'dark:bg-sky-400/[0.07]',
        'dark:hover:bg-sky-400/[0.11]',
    ].join(' '),

    primary: [
        'bg-primary/[0.045]',
        'hover:bg-primary/[0.075]',
        'dark:bg-primary/[0.09]',
        'dark:hover:bg-primary/[0.13]',
    ].join(' '),

    muted: [
        'bg-muted/40',
        'hover:bg-muted/60',
        'dark:bg-muted/35',
        'dark:hover:bg-muted/55',
    ].join(' '),
};

const getRowHighlightClass = (
    highlight?: RowHighlight,
): string => (highlight ? ROW_HIGHLIGHT_CLASSES[highlight] : '');

const getRowClass = (row: any): string[] => {
    const highlight = props.rowHighlight?.(row.original);

    return [
        'group transition-colors',
        hasSelectionColumn.value ? 'cursor-pointer' : '',
        'data-[state=selected]:!bg-primary/10',
        'data-[state=selected]:hover:!bg-primary/15',
        getRowHighlightClass(highlight),
    ];
};

/*
|--------------------------------------------------------------------------
| Column Styles
|--------------------------------------------------------------------------
*/

const FIXED_COLUMN_IDS = new Set([
    SELECT_COLUMN_ID,
    'status',
    'created_at',
    ACTIONS_COLUMN_ID,
]);

const getColumnStyle = (
    column?: any,
): Record<string, string> | undefined => {
    if (!column) {
        return undefined;
    }

    const { id, columnDef } = column;
    const size = column.getSize?.() ?? columnDef?.size;
    const minSize = columnDef?.minSize;
    const maxSize = columnDef?.maxSize;

    if (
        FIXED_COLUMN_IDS.has(id) &&
        typeof size === 'number'
    ) {
        const width = `${size}px`;

        return {
            width,
            minWidth: width,
            maxWidth: width,
        };
    }

    return {
        ...(typeof size === 'number'
            ? { width: `${size}px` }
            : {}),
        minWidth: `${minSize ?? 20}px`,
        ...(typeof maxSize === 'number'
            ? { maxWidth: `${maxSize}px` }
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
): void => {
    if (typeof value !== 'string' && typeof value !== 'number') {
        return;
    }

    const nextPageSize = Number(value);

    if (
        !Number.isInteger(nextPageSize) ||
        !props.pageSizeOptions.includes(nextPageSize)
    ) {
        return;
    }

    props.table.setPagination({
        pageIndex: 0,
        pageSize: nextPageSize,
    });
};
</script>

<template>
    <div
        :aria-busy="loading ? true : undefined"
        :aria-label="`${itemLabel} table`"
        class="w-full min-w-0 overflow-hidden rounded-md border border-border bg-card"
        role="region"
    >
        <!-- Table viewport -->
        <div class="w-full min-w-0 overflow-auto overscroll-contain">
            <UiTable class="table-fixed" :style="tableStyle">
                <TableHeader class="sticky top-0 z-20">
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                        class="border-border bg-muted hover:bg-muted"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            :class="[
                                getColumnClass(header.column.id),
                                getAlignmentClass(getColumnAlign(header.column)),
                                'bg-muted text-foreground capitalize',
                            ]"
                            :style="getColumnStyle(header.column)"
                            class="h-10 px-3 font-medium tracking-wide whitespace-nowrap shadow-[inset_0_-1px_0_var(--border)]"
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
                        <TableRow
                            v-for="rowIndex in loadingRowCount"
                            :key="`loading-${rowIndex}`"
                        >
                            <TableCell
                                v-for="column in loadingColumns"
                                :key="column.id"
                                :data-column-id="column.id"
                                :class="getColumnClass(column.id)"
                                :style="getColumnStyle(column)"
                                class="px-3 py-2.5"
                            >
                                <div
                                    :class="getPlaceholderAlignmentClass(getColumnAlign(column))"
                                    class="flex min-h-7 items-center"
                                >
                                    <div
                                        class="h-4 w-3/4 max-w-32 animate-pulse rounded-sm bg-muted motion-reduce:animate-none"
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
                            :data-state="row.getIsSelected() ? 'selected' : undefined"
                            :class="getRowClass(row)"
                            @click="handleRowClick($event, row)"
                        >
                            <TableCell
                                v-for="cell in row.getAllCells()"
                                :key="cell.id"
                                :data-column-id="cell.column.id"
                                :class="[
                                    getColumnClass(cell.column.id),
                                    getAlignmentClass(getColumnAlign(cell.column)),
                                ]"
                                :style="getColumnStyle(cell.column)"
                                class="px-3 py-2.5 align-middle"
                            >
                                <FlexRender :cell="cell" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- Empty -->
                    <TableRow v-else>
                        <TableCell :colspan="columnsCount" class="p-0">
                            <Empty
                                v-if="emptyState"
                                class="min-h-64 rounded-none border-0 px-6 py-10"
                            >
                                <EmptyHeader>
                                    <EmptyMedia
                                        v-if="emptyState.icon"
                                        variant="icon"
                                    >
                                        <component
                                            :is="emptyState.icon"
                                            class="size-6"
                                            aria-hidden="true"
                                        />
                                    </EmptyMedia>

                                    <EmptyTitle>
                                        {{ emptyState.title }}
                                    </EmptyTitle>

                                    <EmptyDescription
                                        v-if="emptyState.description"
                                    >
                                        {{ emptyState.description }}
                                    </EmptyDescription>
                                </EmptyHeader>

                                <EmptyContent
                                    v-if="emptyState.actionLabel && emptyState.onAction"
                                >
                                    <Button
                                        type="button"
                                        size="sm"
                                        @click="emptyState.onAction"
                                    >
                                        {{ emptyState.actionLabel }}
                                    </Button>
                                </EmptyContent>
                            </Empty>

                            <div
                                v-else
                                class="flex min-h-32 items-center justify-center px-4 text-center"
                            >
                                <span class="text-sm text-muted-foreground">
                                    {{ emptyText }}
                                </span>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </UiTable>
        </div>

        <!-- Pagination -->
        <div
            v-if="total > 0"
            class="border-t px-4 py-3 sm:px-5"
        >
            <div class="flex flex-col gap-4">
                <!-- Summary -->
                <div class="min-w-0">
                    <p
                        aria-live="polite"
                        class="text-sm wrap-break-word text-muted-foreground"
                    >
                        Showing
                        <span class="font-medium text-foreground">
                            {{ showingFrom }}–{{ showingTo }}
                        </span>
                        of
                        <span class="font-medium text-foreground">
                            {{ total }}
                        </span>
                        {{ itemLabel }}

                        <template v-if="selectedCount > 0">
                            <span class="mx-1.5">·</span>
                            <span class="font-medium text-foreground">
                                {{ selectedCount }}
                            </span>
                            selected
                        </template>
                    </p>
                </div>

                <!-- Pagination controls -->
                <div
                    class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
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

                    <div class="max-w-full min-w-0 overflow-x-auto pb-0.5">
                        <nav
                            aria-label="Pagination"
                            class="flex w-max items-center gap-1"
                        >
                            <Button
                                :disabled="loading || !table.getCanPreviousPage()"
                                aria-label="Previous page"
                                class="size-9 shrink-0"
                                size="icon"
                                variant="outline"
                                @click="table.previousPage()"
                            >
                                <ChevronLeft aria-hidden="true" class="size-4" />
                            </Button>

                            <template
                                v-for="item in paginationItems"
                                :key="item.type === 'page' ? item.page : item.key"
                            >
                                <Button
                                    v-if="item.type === 'page'"
                                    :aria-current="pageIndex === item.page - 1 ? 'page' : undefined"
                                    :aria-label="`Page ${item.page}`"
                                    :disabled="loading"
                                    :variant="pageIndex === item.page - 1 ? 'default' : 'ghost'"
                                    class="size-9 shrink-0"
                                    size="icon"
                                    @click="table.setPageIndex(item.page - 1)"
                                >
                                    {{ item.page }}
                                </Button>

                                <span
                                    v-else
                                    aria-hidden="true"
                                    class="flex size-9 shrink-0 items-center justify-center text-sm text-muted-foreground"
                                >
                                    …
                                </span>
                            </template>

                            <Button
                                :disabled="loading || !table.getCanNextPage()"
                                aria-label="Next page"
                                class="size-9 shrink-0"
                                size="icon"
                                variant="outline"
                                @click="table.nextPage()"
                            >
                                <ChevronRight aria-hidden="true" class="size-4" />
                            </Button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
