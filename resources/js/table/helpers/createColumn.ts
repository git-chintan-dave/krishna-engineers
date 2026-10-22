import { ArrowDown, ArrowUp, ChevronsUpDown } from '@lucide/vue';
import { h } from 'vue';

import type { ColumnAlign } from '@/table/types';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ColumnMeta = {
    align: ColumnAlign;
    sortKey?: string;
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
    primary?: boolean;
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

const getColumnWidth = (size?: number, minSize?: number, maxSize?: number) => ({
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
| Sortable Header
|--------------------------------------------------------------------------
*/

const createHeader =
    (label: string, align: ColumnAlign) =>
    ({ column }: SortableHeaderContext) =>
        h(
            'button',
            {
                type: 'button',
                class: [
                    'flex w-full min-w-0 items-center gap-2 cursor-pointer',
                    'text-sm font-medium leading-5',
                    'whitespace-normal break-words hover:text-foreground',
                    getHeaderAlignmentClass(align),
                ],
                onClick: () =>
                    column.toggleSorting(column.getIsSorted() === 'asc'),
            },
            [
                h(
                    'span',
                    {
                        class: 'min-w-0 whitespace-normal break-words',
                    },
                    label,
                ),
                h(
                    column.getIsSorted() === 'asc'
                        ? ArrowUp
                        : column.getIsSorted() === 'desc'
                          ? ArrowDown
                          : ChevronsUpDown,
                    { class: 'size-4 shrink-0' },
                ),
            ],
        );

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
            ? { id: options.id, accessorFn: options.accessorFn }
            : { accessorKey: options.accessorKey };

    const columnMeta: ColumnMeta = {
        ...meta,
        align,
        ...(sortKey !== undefined ? { sortKey } : {}),
    };

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
                              'block w-full text-sm font-medium leading-5',
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
                            'block min-w-0 whitespace-normal break-words text-sm',
                            primary ? 'font-semibold' : 'font-normal',
                            getAlignmentClass(align),
                        ],
                    },
                    formatCellValue(context.getValue()),
                )),
        ...getColumnWidth(size, minSize, maxSize),
    };
};
