import {h} from 'vue';

import {ArrowDown, ArrowUp, ChevronsUpDown} from '@lucide/vue';

import type {ColumnAlign} from '@/table/types';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SortableHeaderOptions = {
    label: string;
    align?: ColumnAlign;
};

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getAlignmentClass = (align: ColumnAlign): string => {
    switch (align) {
        case 'center':
            return 'justify-center text-center';
        case 'right':
            return 'justify-end text-right';
        default:
            return 'justify-start text-left';
    }
};

/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

export const createSortableHeader = ({
                                         label,
                                         align = 'left',
                                     }: SortableHeaderOptions) =>
    ({ column }: any) =>
        h(
            'button',
            {
                type: 'button',
                class: [
                    'flex w-full min-w-0 cursor-pointer items-center gap-1.5',
                    'text-sm font-medium leading-5',
                    'whitespace-normal break-words',
                    'hover:text-foreground',
                    getAlignmentClass(align),
                ],
                onClick: () =>
                    column.toggleSorting(
                        column.getIsSorted() === 'asc',
                    ),
            },
            [
                h(
                    'span',
                    {
                        class: 'min-w-0 flex-1 whitespace-normal break-words',
                    },
                    label,
                ),

                h(
                    column.getIsSorted() === 'asc'
                        ? ArrowUp
                        : column.getIsSorted() === 'desc'
                            ? ArrowDown
                            : ChevronsUpDown,
                    {
                        class: 'size-4 shrink-0',
                    },
                ),
            ],
        );
