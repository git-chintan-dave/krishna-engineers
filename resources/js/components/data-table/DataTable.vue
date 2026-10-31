<script lang="ts" setup>
import {type Component, computed, nextTick, onBeforeUnmount, onMounted, ref,} from 'vue';
import {FlexRender} from '@tanstack/vue-table';
import {ChevronLeft, ChevronRight} from '@lucide/vue';

import AppSelect from '@/components/common/AppSelect.vue';
import {Button} from '@/components/ui/button';
import {Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle,} from '@/components/ui/empty';
import {Table as UiTable, TableBody, TableCell, TableHead, TableHeader, TableRow,} from '@/components/ui/table';
import {Skeleton} from '@/components/ui/skeleton';

type ColumnAlign = 'left' | 'center' | 'right';
type ColumnPin = 'left' | 'right';

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
    | {
    type: 'page';
    page: number;
}
    | {
    type: 'ellipsis';
    key: string;
};

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

const SELECT_COLUMN_ID = 'select';
const ACTIONS_COLUMN_ID = 'actions';

const DESKTOP_BREAKPOINT = '(min-width: 768px)';

const FIXED_COLUMN_IDS = new Set([
    SELECT_COLUMN_ID,
    'status',
    'created_at',
    ACTIONS_COLUMN_ID,
]);

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

const props = withDefaults(defineProps<Props>(), {
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
});

const isDesktop = ref(false);

let desktopMediaQuery: MediaQueryList | undefined;

const tableScrollRef = ref<HTMLElement | null>(null);
const tableHeaderRef = ref<HTMLElement | null>(null);
const tableHeaderTableRef =
    ref<HTMLElement | null>(null);

const tableScrollLeft = ref(0);

const handleDesktopChange = (
    event: MediaQueryListEvent,
): void => {
    isDesktop.value = event.matches;
};

const syncTableHeaderScroll = (): void => {
    const container = tableScrollRef.value;

    if (!container) {
        return;
    }

    tableScrollLeft.value = container.scrollLeft;
};

onMounted(async () => {
    desktopMediaQuery = window.matchMedia(
        DESKTOP_BREAKPOINT,
    );

    isDesktop.value = desktopMediaQuery.matches;

    desktopMediaQuery.addEventListener(
        'change',
        handleDesktopChange,
    );

    await nextTick();

    const container = tableScrollRef.value;

    if (container) {
        container.addEventListener(
            'scroll',
            syncTableHeaderScroll,
            { passive: true },
        );

        syncTableHeaderScroll();
    }
});

onBeforeUnmount(() => {
    desktopMediaQuery?.removeEventListener(
        'change',
        handleDesktopChange,
    );

    tableScrollRef.value?.removeEventListener(
        'scroll',
        syncTableHeaderScroll,
    );
});

const visibleColumns = computed(() => {
    const table = props.table;

    if (
        typeof table?.getVisibleLeafColumns ===
        'function'
    ) {
        return table.getVisibleLeafColumns();
    }

    return (
        table?.getAllLeafColumns?.() ?? []
    ).filter(
        (column: any) =>
            column.getIsVisible?.() !== false,
    );
});

const loadingColumns = computed(
    () => visibleColumns.value,
);

const hasSelectionColumn = computed(() =>
    visibleColumns.value.some(
        (column: any) =>
            column.id === SELECT_COLUMN_ID,
    ),
);

const handleRowClick = (
    event: MouseEvent,
    row: any,
): void => {
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

    if (
        target.closest(INTERACTIVE_SELECTOR)
    ) {
        return;
    }

    row.toggleSelected();
};

const getColumnSize = (
    column: any,
): number | undefined => {
    const size =
        column?.getSize?.() ??
        column?.columnDef?.size;

    return typeof size === 'number'
        ? size
        : undefined;
};

const getColumnRenderedWidth = (
    column: any,
): number => {
    const size = getColumnSize(column);

    const minSize =
        column?.columnDef?.minSize;

    const maxSize =
        column?.columnDef?.maxSize;

    let width =
        typeof size === 'number'
            ? size
            : typeof minSize === 'number'
                ? minSize
                : 150;

    if (typeof minSize === 'number') {
        width = Math.max(width, minSize);
    }

    if (typeof maxSize === 'number') {
        width = Math.min(width, maxSize);
    }

    return width;
};

const getColumnMinimumWidth = (
    column: any,
): number => {
    return getColumnRenderedWidth(column);
};

const tableMinWidth = computed(() =>
    visibleColumns.value.reduce(
        (
            total: number,
            column: any,
        ) =>
            total +
            getColumnMinimumWidth(column),
        0,
    ),
);

const tableStyle = computed(() => ({
    width: `max(100%, ${tableMinWidth.value}px)`,
}));

const getColumnClass = (
    columnId: string,
): string => {
    const widthClass =
        props.columnWidths[columnId] ?? '';

    if (columnId === SELECT_COLUMN_ID) {
        return [
            widthClass,
            'px-0',
        ]
            .filter(Boolean)
            .join(' ');
    }

    return widthClass;
};

const getColumnAlign = (
    column?: any,
): ColumnAlign => {
    const align =
        column?.columnDef?.meta?.align;

    return align === 'center' ||
    align === 'right'
        ? align
        : 'left';
};

const getAlignmentClass = (
    align: ColumnAlign,
) => ({
    'text-left': align === 'left',
    'text-center': align === 'center',
    'text-right': align === 'right',
});

const getPlaceholderAlignmentClass = (
    align: ColumnAlign,
) => ({
    'justify-start': align === 'left',
    'justify-center': align === 'center',
    'justify-end': align === 'right',
});

const ROW_HIGHLIGHT_CLASSES: Record<
    RowHighlight,
    string
> = {
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
): string =>
    highlight
        ? ROW_HIGHLIGHT_CLASSES[highlight]
        : '';

const getRowClass = (row: any): string[] => {
    const highlight = props.rowHighlight?.(row.original);

    return [
        'group',
        'transition-none',

        hasSelectionColumn.value ? 'cursor-pointer' : '',

        !highlight ? 'hover:bg-muted/50' : '',

        'data-[state=selected]:!bg-primary/10',
        'data-[state=selected]:hover:!bg-primary/15',

        getRowHighlightClass(highlight),
    ];
};

const getColumnPin = (
    column: any,
): ColumnPin | undefined => {
    if (!isDesktop.value) {
        return undefined;
    }

    const pin =
        column?.columnDef?.meta?.pin;

    return pin === 'left' || pin === 'right'
        ? pin
        : undefined;
};

const getPinnedOffset = (
    column: any,
): number => {
    const pin = getColumnPin(column);

    if (!pin) {
        return 0;
    }

    const columns = visibleColumns.value;

    const columnIndex = columns.findIndex(
        (item: any) =>
            item.id === column.id,
    );

    if (columnIndex === -1) {
        return 0;
    }

    const adjacentColumns =
        pin === 'left'
            ? columns.slice(0, columnIndex)
            : columns.slice(columnIndex + 1);

    return adjacentColumns.reduce(
        (
            offset: number,
            item: any,
        ) => {
            if (
                getColumnPin(item) !== pin
            ) {
                return offset;
            }

            return (
                offset +
                getColumnRenderedWidth(item)
            );
        },
        0,
    );
};

const getColumnStyle = (
    column?: any,
    isHeader = false,
): Record<string, string> | undefined => {
    if (!column) {
        return undefined;
    }

    const {
        id,
        columnDef,
    } = column;

    const size = getColumnSize(column);
    const minSize = columnDef?.minSize;
    const maxSize = columnDef?.maxSize;
    const pin = getColumnPin(column);

    const style: Record<string, string> = {};

    if (
        FIXED_COLUMN_IDS.has(id) &&
        typeof size === 'number'
    ) {
        const width = `${size}px`;

        style.width = width;
        style.minWidth = width;
        style.maxWidth = width;
    } else {
        if (typeof size === 'number') {
            style.width = `${size}px`;
        }

        style.minWidth = `${minSize ?? 20}px`;

        if (typeof maxSize === 'number') {
            style.maxWidth = `${maxSize}px`;
        }
    }

    if (pin) {
        style.position = 'sticky';
        style.zIndex = isHeader
            ? '20'
            : '10';

        if (pin === 'left') {
            style.left = `${getPinnedOffset(column)}px`;
        } else {
            style.right = `${getPinnedOffset(column)}px`;
        }
    } else if (isHeader) {
        style.zIndex = '20';
    }

    style.boxShadow = 'inset 0 -1px 0 var(--border)';
    // style.boxShadow = 'inset -1px 0 0 var(--border), inset 0 -1px 0 var(--border)';

    if (pin === 'left') {
        style.boxShadow =
            'inset -1px 0 0 var(--border), inset 0 -1px 0 var(--border)';
    } else if (pin === 'right') {
        style.boxShadow =
            'inset 1px 0 0 var(--border), inset 0 -1px 0 var(--border)';
    }

    return style;
};

const getHeaderColumnStyle = (
    column: any,
): Record<string, string> => {
    const style =
        getColumnStyle(
            column,
            true,
        ) ?? {};

    const pin = getColumnPin(column);

    if (pin) {
        style.transform = `translateX(${tableScrollLeft.value}px)`;
    }

    return style;
};

const PINNED_HIGHLIGHT_CLASSES: Record<RowHighlight, string> = {
    danger: [
        'bg-[color-mix(in_srgb,var(--card)_95.5%,#ef4444_4.5%)]',
        'group-hover:bg-[color-mix(in_srgb,var(--card)_92.5%,#ef4444_7.5%)]',
        'dark:bg-[color-mix(in_srgb,var(--card)_93%,#f87171_7%)]',
        'dark:group-hover:bg-[color-mix(in_srgb,var(--card)_89%,#f87171_11%)]',
    ].join(' '),

    warning: [
        'bg-[color-mix(in_srgb,var(--card)_94.5%,#f59e0b_5.5%)]',
        'group-hover:bg-[color-mix(in_srgb,var(--card)_91.5%,#f59e0b_8.5%)]',
        'dark:bg-[color-mix(in_srgb,var(--card)_93%,#fbbf24_7%)]',
        'dark:group-hover:bg-[color-mix(in_srgb,var(--card)_89%,#fbbf24_11%)]',
    ].join(' '),

    success: [
        'bg-[color-mix(in_srgb,var(--card)_95.5%,#10b981_4.5%)]',
        'group-hover:bg-[color-mix(in_srgb,var(--card)_92.5%,#10b981_7.5%)]',
        'dark:bg-[color-mix(in_srgb,var(--card)_93%,#34d399_7%)]',
        'dark:group-hover:bg-[color-mix(in_srgb,var(--card)_89%,#34d399_11%)]',
    ].join(' '),

    info: [
        'bg-[color-mix(in_srgb,var(--card)_95.5%,#0ea5e9_4.5%)]',
        'group-hover:bg-[color-mix(in_srgb,var(--card)_92.5%,#0ea5e9_7.5%)]',
        'dark:bg-[color-mix(in_srgb,var(--card)_93%,#38bdf8_7%)]',
        'dark:group-hover:bg-[color-mix(in_srgb,var(--card)_89%,#38bdf8_11%)]',
    ].join(' '),

    primary: [
        'bg-[color-mix(in_srgb,var(--card)_95.5%,var(--primary)_4.5%)]',
        'group-hover:bg-[color-mix(in_srgb,var(--card)_92.5%,var(--primary)_7.5%)]',
        'dark:bg-[color-mix(in_srgb,var(--card)_91%,var(--primary)_9%)]',
        'dark:group-hover:bg-[color-mix(in_srgb,var(--card)_87%,var(--primary)_13%)]',
    ].join(' '),

    muted: [
        'bg-[color-mix(in_srgb,var(--card)_60%,var(--muted)_40%)]',
        'group-hover:bg-[color-mix(in_srgb,var(--card)_40%,var(--muted)_60%)]',
        'dark:bg-[color-mix(in_srgb,var(--card)_65%,var(--muted)_35%)]',
        'dark:group-hover:bg-[color-mix(in_srgb,var(--card)_45%,var(--muted)_55%)]',
    ].join(' '),
};

const getPinnedCellClass = (
    column: any,
    row?: any,
): string => {
    if (!getColumnPin(column)) {
        return '';
    }

    const highlight = row
        ? props.rowHighlight?.(row.original)
        : undefined;

    return [
        'isolate',
        'bg-card',
        'bg-clip-padding',
        'transition-none',

        highlight
            ? PINNED_HIGHLIGHT_CLASSES[highlight]
            : 'group-hover:bg-[color-mix(in_srgb,var(--card)_50%,var(--muted)_50%)]',

        'group-data-[state=selected]:!bg-[color-mix(in_srgb,var(--card)_88%,var(--primary)_12%)]',
        'group-data-[state=selected]:group-hover:!bg-[color-mix(in_srgb,var(--card)_85%,var(--primary)_15%)]',
    ]
        .filter(Boolean)
        .join(' ');
};

const getPinnedHeaderClass = (
    column: any,
): string =>
    getColumnPin(column)
        ? 'bg-muted bg-clip-padding'
        : '';

const loadingRowCount = computed(() =>
    Math.min(
        Math.max(
            props.pageSize || 10,
            5,
        ),
        10,
    ),
);

const pageSizeOptions = computed(() =>
    props.pageSizeOptions.map((size) => ({
        value: size,
        label: String(size),
    })),
);

const showingFrom = computed(() =>
    props.total > 0
        ? props.pageIndex *
        props.pageSize +
        1
        : 0,
);

const showingTo = computed(() =>
    Math.min(
        (props.pageIndex + 1) *
        props.pageSize,
        props.total,
    ),
);

const paginationItems = computed<
    PaginationItem[]
>(() => {
    const pageCount =
        props.table.getPageCount();

    const currentPage =
        props.pageIndex + 1;

    if (pageCount <= 7) {
        return Array.from(
            { length: pageCount },
            (_, index) => ({
                type: 'page' as const,
                page: index + 1,
            }),
        );
    }

    const items: PaginationItem[] = [];

    const addPage = (
        page: number,
    ): void => {
        items.push({
            type: 'page',
            page,
        });
    };

    const addEllipsis = (
        key: string,
    ): void => {
        items.push({
            type: 'ellipsis',
            key,
        });
    };

    addPage(1);

    if (currentPage > 4) {
        addEllipsis('start');
    }

    for (
        let page = Math.max(
            2,
            currentPage - 1,
        );
        page <=
        Math.min(
            pageCount - 1,
            currentPage + 1,
        );
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

const handlePageSizeChange = (
    value:
        | string
        | number
        | (string | number)[]
        | null,
): void => {
    if (
        typeof value !== 'string' &&
        typeof value !== 'number'
    ) {
        return;
    }

    const nextPageSize = Number(value);

    if (
        !Number.isInteger(
            nextPageSize,
        ) ||
        !props.pageSizeOptions.includes(
            nextPageSize,
        )
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
        :aria-busy="
            loading ? true : undefined
        "
        :aria-label="`${itemLabel} table`"
        class="w-full min-w-0 overflow-clip rounded-md border border-border bg-card"
        role="region"
    >
        <div
            ref="tableHeaderRef"
            class="sticky top-[calc(var(--app-header-height,0px)+var(--app-page-header-height,0px))] z-20 w-full min-w-0 overflow-hidden border-b border-border bg-muted"
        >
            <UiTable
                ref="tableHeaderTableRef"
                class="table-fixed border-separate border-spacing-0"
                :style="{
                    ...tableStyle,
                    transform: `translateX(-${tableScrollLeft}px)`,
                }"
            >
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                        class="border-border bg-muted hover:bg-muted"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers.filter(
                                (item: any) =>
                                    item.column.getIsVisible?.() !== false,
                            )"
                            :key="header.id"
                            :class="[
                                getColumnClass(
                                    header.column.id,
                                ),

                                getAlignmentClass(
                                    getColumnAlign(
                                        header.column,
                                    ),
                                ),

                                'bg-muted text-foreground capitalize',

                                getPinnedHeaderClass(
                                    header.column,
                                ),

                                header.column.id ===
                                SELECT_COLUMN_ID
                                    ? 'px-0'
                                    : 'px-3',
                            ]"
                            :style="
                                getHeaderColumnStyle(
                                    header.column,
                                )
                            "
                            class="h-10 whitespace-nowrap text-sm font-medium tracking-wide"
                        >
                            <div
                                v-if="
                                    !header.isPlaceholder
                                "
                                :class="
                                    header.column.id ===
                                    SELECT_COLUMN_ID
                                        ? 'flex w-full items-center justify-center'
                                        : ''
                                "
                            >
                                <FlexRender
                                    :header="header"
                                />
                            </div>
                        </TableHead>
                    </TableRow>
                </TableHeader>
            </UiTable>
        </div>

        <div
            ref="tableScrollRef"
            class="w-full min-w-0 overflow-x-auto overflow-y-visible overscroll-x-contain"
        >
            <UiTable
                class="table-fixed border-separate border-spacing-0"
                :style="tableStyle"
            >
                <TableBody>
                    <template
                        v-if="loading"
                    >
                        <TableRow
                            v-for="rowIndex in loadingRowCount"
                            :key="`loading-${rowIndex}`"
                            class="group"
                        >
                            <TableCell
                                v-for="column in loadingColumns"
                                :key="column.id"
                                :data-column-id="
                                    column.id
                                "
                                :class="[
                                    getColumnClass(
                                        column.id,
                                    ),

                                    getPinnedCellClass(
                                        column,
                                    ),

                                    column.id ===
                                    SELECT_COLUMN_ID
                                        ? 'px-0'
                                        : 'px-3',
                                ]"
                                :style="
                                    getColumnStyle(
                                        column,
                                    )
                                "
                                class="py-2.5 align-middle"
                            >
                                <div
                                    :class="[
                                        getPlaceholderAlignmentClass(
                                            getColumnAlign(
                                                column,
                                            ),
                                        ),

                                        column.id ===
                                        SELECT_COLUMN_ID
                                            ? 'justify-center'
                                            : '',
                                    ]"
                                    class="flex min-h-8 items-center"
                                >
                                    <div
                                        :class="
                                            column.id ===
                                            SELECT_COLUMN_ID
                                                ? 'size-4'
                                                : 'h-4 min-w-4 w-3/4 max-w-32'
                                        "
                                        class="animate-pulse rounded-sm bg-muted motion-reduce:animate-none"
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>

                    <template
                        v-else-if="
                            table.getRowModel().rows
                                .length
                        "
                    >
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="
                                row.getIsSelected()
                                    ? 'selected'
                                    : undefined
                            "
                            :class="
                                getRowClass(row)
                            "
                            @click="
                                handleRowClick(
                                    $event,
                                    row,
                                )
                            "
                        >
                            <TableCell
                                v-for="cell in row
                                    .getAllCells()
                                    .filter(
                                        (item: any) =>
                                            item.column.getIsVisible?.() !== false,
                                    )"
                                :key="cell.id"
                                :data-column-id="
                                    cell.column.id
                                "
                                :class="[
                                    getColumnClass(
                                        cell.column.id,
                                    ),

                                    getAlignmentClass(
                                        getColumnAlign(
                                            cell.column,
                                        ),
                                    ),

                                    getPinnedCellClass(
                                        cell.column,
                                        row,
                                    ),

                                    cell.column.id ===
                                    SELECT_COLUMN_ID
                                        ? 'px-0'
                                        : 'px-3',
                                ]"
                                :style="
                                    getColumnStyle(
                                        cell.column,
                                    )
                                "
                                class="py-2.5 align-middle"
                            >
                                <div
                                    :class="
                                        cell.column.id ===
                                        SELECT_COLUMN_ID
                                            ? 'flex w-full items-center justify-center'
                                            : ''
                                    "
                                >
                                    <FlexRender
                                        :cell="cell"
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>

                    <TableRow v-else>
                        <TableCell
                            :colspan="
                                visibleColumns.length ||
                                columnsCount
                            "
                            class="p-0"
                        >
                            <Empty
                                v-if="emptyState"
                                class="min-h-64 rounded-none border-0 px-6 py-10"
                            >
                                <EmptyHeader>
                                    <EmptyMedia
                                        v-if="
                                            emptyState.icon
                                        "
                                        variant="icon"
                                    >
                                        <component
                                            :is="
                                                emptyState.icon
                                            "
                                            class="size-6"
                                            aria-hidden="true"
                                        />
                                    </EmptyMedia>

                                    <EmptyTitle>
                                        {{
                                            emptyState.title
                                        }}
                                    </EmptyTitle>

                                    <EmptyDescription
                                        v-if="
                                            emptyState.description
                                        "
                                    >
                                        {{
                                            emptyState.description
                                        }}
                                    </EmptyDescription>
                                </EmptyHeader>

                                <EmptyContent
                                    v-if="
                                        emptyState.actionLabel &&
                                        emptyState.onAction
                                    "
                                >
                                    <Button
                                        type="button"
                                        size="sm"
                                        @click="
                                            emptyState.onAction
                                        "
                                    >
                                        {{
                                            emptyState.actionLabel
                                        }}
                                    </Button>
                                </EmptyContent>
                            </Empty>

                            <div
                                v-else
                                class="flex min-h-32 items-center justify-center px-4 text-center"
                            >
                                <span
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ emptyText }}
                                </span>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </UiTable>
        </div>

        <div
            v-if="total > 0 || loading"
            class="border-t border-border px-4 py-3 sm:px-5"
        >
            <div class="flex flex-col gap-4">
                <div class="min-w-0">
                    <template v-if="loading">
                        <div
                            class="flex h-5 items-center gap-2"
                        >
                            <Skeleton
                                class="h-4 w-16"
                            />
                            <Skeleton
                                class="h-4 w-20"
                            />
                            <Skeleton
                                class="h-4 w-12"
                            />
                            <Skeleton
                                class="h-4 w-16"
                            />
                        </div>
                    </template>

                    <p
                        v-else
                        aria-live="polite"
                        class="text-sm wrap-break-word text-muted-foreground"
                    >
                        Showing
                        <span
                            class="font-medium text-foreground"
                        >
                            {{ showingFrom }}–{{
                                showingTo
                            }}
                        </span>
                        of
                        <span
                            class="font-medium text-foreground"
                        >
                            {{ total }}
                        </span>
                        {{ itemLabel }}

                        <template
                            v-if="selectedCount > 0"
                        >
                            <span
                                class="mx-1.5"
                            >
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

                <div
                    class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div
                        class="flex min-w-0 items-center gap-2"
                    >
                        <template v-if="loading">
                            <Skeleton
                                class="h-4 w-24"
                            />
                            <Skeleton
                                class="h-9 w-24 rounded-md"
                            />
                        </template>

                        <template v-else>
                            <span
                                class="shrink-0 text-sm whitespace-nowrap text-muted-foreground"
                            >
                                Rows per page
                            </span>

                            <div
                                class="w-24 shrink-0"
                            >
                                <AppSelect
                                    :disabled="loading"
                                    :model-value="
                                        pageSize
                                    "
                                    :options="
                                        pageSizeOptions
                                    "
                                    placeholder="10"
                                    @update:model-value="
                                        handlePageSizeChange
                                    "
                                />
                            </div>
                        </template>
                    </div>

                    <div
                        class="max-w-full min-w-0 overflow-x-auto pb-0.5"
                    >
                        <nav
                            v-if="loading"
                            aria-hidden="true"
                            class="flex w-max items-center gap-1"
                        >
                            <Skeleton
                                class="size-9 shrink-0 rounded-md"
                            />
                            <Skeleton
                                class="size-9 shrink-0 rounded-md"
                            />
                            <Skeleton
                                class="size-9 shrink-0 rounded-md"
                            />
                            <Skeleton
                                class="size-9 shrink-0 rounded-md"
                            />
                            <Skeleton
                                class="size-9 shrink-0 rounded-md"
                            />
                        </nav>

                        <nav
                            v-else
                            aria-label="Pagination"
                            class="flex w-max items-center gap-1"
                        >
                            <Button
                                :disabled="
                                    !table.getCanPreviousPage()
                                "
                                aria-label="Previous page"
                                class="size-9 shrink-0"
                                size="icon"
                                variant="outline"
                                @click="
                                    table.previousPage()
                                "
                            >
                                <ChevronLeft
                                    aria-hidden="true"
                                    class="size-4"
                                />
                            </Button>

                            <template
                                v-for="item in paginationItems"
                                :key="
                                    item.type ===
                                    'page'
                                        ? item.page
                                        : item.key
                                "
                            >
                                <Button
                                    v-if="
                                        item.type ===
                                        'page'
                                    "
                                    :aria-current="
                                        pageIndex ===
                                        item.page - 1
                                            ? 'page'
                                            : undefined
                                    "
                                    :aria-label="`Page ${item.page}`"
                                    :variant="
                                        pageIndex ===
                                        item.page - 1
                                            ? 'default'
                                            : 'ghost'
                                    "
                                    class="size-9 shrink-0"
                                    size="icon"
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
                                    aria-hidden="true"
                                    class="flex size-9 shrink-0 items-center justify-center text-sm text-muted-foreground"
                                >
                                    …
                                </span>
                            </template>

                            <Button
                                :disabled="
                                    !table.getCanNextPage()
                                "
                                aria-label="Next page"
                                class="size-9 shrink-0"
                                size="icon"
                                variant="outline"
                                @click="
                                    table.nextPage()
                                "
                            >
                                <ChevronRight
                                    aria-hidden="true"
                                    class="size-4"
                                />
                            </Button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
