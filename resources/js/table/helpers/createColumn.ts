import {ArrowDown, ArrowUp, ChevronsUpDown} from '@lucide/vue';
import {h, onBeforeUnmount, onMounted, reactive,} from 'vue';

import type {ColumnAlign} from '@/table/types';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

export type ColumnPin = 'left' | 'right';

type ColumnMeta = {
    align: ColumnAlign;
    sortKey?: string;
    pin?: ColumnPin;
    [key: string]: unknown;
};

type CellContext<TData, TValue> = {
    getValue: () => TValue;
    row: {
        original: TData;
    };
};

type SortableHeaderContext = {
    column: {
        toggleSorting: (desc?: boolean) => void;
        getIsSorted: () => false | 'asc' | 'desc';
    };
};

type BaseColumnOptions<TData, TValue> = {
    header: string;
    sortable?: boolean;
    enableSorting?: boolean;

    /**
     * Marks the column as the primary/important data column.
     *
     * Primary:
     * - text-sm
     * - font-medium
     *
     * Normal:
     * - text-sm
     * - font-normal
     */
    primary?: boolean;

    /**
     * Pin the column to the left or right side of the table.
     *
     * Pinning is enabled only on desktop (768px and above).
     * The DataTable is responsible for applying sticky positioning
     * and calculating the required offset.
     */
    pin?: ColumnPin;

    size?: number;
    minSize?: number;
    maxSize?: number;
    align?: ColumnAlign;
    sortKey?: string;
    cell?: (context: CellContext<TData, TValue>) => unknown;
    meta?: Record<string, unknown>;
};

type AccessorKeyColumnOptions<TData, TValue> = BaseColumnOptions<
    TData,
    TValue
> & {
    accessorKey: string;
    accessorFn?: never;
    id?: never;
};

type AccessorFnColumnOptions<TData, TValue> = BaseColumnOptions<
    TData,
    TValue
> & {
    accessorFn: (row: TData, index: number) => TValue;
    accessorKey?: never;
    id: string;
};

export type CreateColumnOptions<TData, TValue = unknown> =
    | AccessorKeyColumnOptions<TData, TValue>
    | AccessorFnColumnOptions<TData, TValue>;

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const DESKTOP_BREAKPOINT = '(min-width: 768px)';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getAlignmentClass = (align: ColumnAlign): string => {
    switch (align) {
        case 'center':
            return 'text-center';

        case 'right':
            return 'text-right';

        default:
            return 'text-left';
    }
};

const getHeaderAlignmentClass = (align: ColumnAlign): string => {
    switch (align) {
        case 'center':
            return 'justify-center text-center';

        case 'right':
            return 'justify-end text-right';

        default:
            return 'justify-start text-left';
    }
};

const formatCellValue = (value: unknown): string => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
};

const getColumnWidth = (
    size?: number,
    minSize?: number,
    maxSize?: number,
) => ({
    ...(size !== undefined ? { size } : {}),

    ...(minSize !== undefined
        ? { minSize }
        : size !== undefined
            ? { minSize: size }
            : {}),

    ...(maxSize !== undefined
        ? { maxSize }
        : size !== undefined
            ? { maxSize: size }
            : {}),
});

/*
|--------------------------------------------------------------------------
| Responsive Column Meta
|--------------------------------------------------------------------------
*/

const createColumnMeta = (
    meta: Record<string, unknown> | undefined,
    align: ColumnAlign,
    sortKey: string | undefined,
    pin: ColumnPin | undefined,
): ColumnMeta => {
    const columnMeta = reactive<ColumnMeta>({
        ...meta,
        align,
        ...(sortKey !== undefined ? { sortKey } : {}),
    });

    if (!pin) {
        return columnMeta;
    }

    let mediaQuery: MediaQueryList | undefined;

    const updatePin = (matches: boolean) => {
        if (matches) {
            columnMeta.pin = pin;
        } else {
            delete columnMeta.pin;
        }
    };

    onMounted(() => {
        mediaQuery = window.matchMedia(DESKTOP_BREAKPOINT);

        updatePin(mediaQuery.matches);

        mediaQuery.addEventListener('change', (event) => {
            updatePin(event.matches);
        });
    });

    onBeforeUnmount(() => {
        if (mediaQuery) {
            mediaQuery.removeEventListener('change', handleChange);
        }
    });

    function handleChange(event: MediaQueryListEvent) {
        updatePin(event.matches);
    }

    return columnMeta;
};

/*
|--------------------------------------------------------------------------
| Sortable Header
|--------------------------------------------------------------------------
*/

const createHeader =
    (label: string, align: ColumnAlign) =>
        ({ column }: SortableHeaderContext) => {
            const sorted = column.getIsSorted();

            const SortIcon =
                sorted === 'asc'
                    ? ArrowUp
                    : sorted === 'desc'
                        ? ArrowDown
                        : ChevronsUpDown;

            return h(
                'button',
                {
                    type: 'button',
                    class: [
                        'flex w-full min-w-0 cursor-pointer items-center gap-2',
                        'text-sm font-medium leading-5',
                        'whitespace-normal break-words',
                        'hover:text-foreground',
                        'focus-visible:outline-none',
                        'focus-visible:ring-2',
                        'focus-visible:ring-ring/50',
                        'focus-visible:ring-offset-1',
                        getHeaderAlignmentClass(align),
                    ],
                    onClick: () => column.toggleSorting(sorted === 'asc'),
                },
                [
                    h(
                        'span',
                        {
                            class: 'min-w-0 whitespace-normal break-words',
                        },
                        label,
                    ),

                    h(SortIcon, {
                        class: 'size-4 shrink-0',
                    }),
                ],
            );
        };

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createColumn = <TData, TValue = unknown>(
    options: CreateColumnOptions<TData, TValue>,
) => {
    const {
        header,
        sortable = false,
        enableSorting = sortable,
        primary = false,
        pin,
        size,
        minSize,
        maxSize,
        align = 'left',
        sortKey,
        cell,
        meta,
    } = options;

    const accessor =
        'accessorFn' in options && options.accessorFn
            ? {
                id: options.id,
                accessorFn: options.accessorFn,
            }
            : {
                accessorKey: options.accessorKey,
            };

    const columnMeta = createColumnMeta(
        meta,
        align,
        sortKey,
        pin,
    );

    return {
        ...accessor,

        enableSorting,

        meta: columnMeta,

        header: sortable
            ? createHeader(header, align)
            : () =>
                h(
                    'span',
                    {
                        class: [
                            'block w-full',
                            'text-sm font-medium leading-5',
                            'whitespace-normal break-words',
                            getHeaderAlignmentClass(align),
                        ],
                    },
                    header,
                ),

        cell:
            cell ??
            ((context: CellContext<TData, TValue>) =>
                h(
                    'span',
                    {
                        class: [
                            'block min-w-0 whitespace-normal break-words',
                            primary
                                ? 'text-sm font-medium'
                                : 'text-sm font-normal',
                            getAlignmentClass(align),
                        ],
                    },
                    formatCellValue(context.getValue()),
                )),

        ...getColumnWidth(size, minSize, maxSize),
    };
};
