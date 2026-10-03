import { h } from 'vue';

import { ArrowDown, ArrowUp, ChevronsUpDown } from '@lucide/vue';

type SortableHeaderOptions = {
    label: string;
};

export const createSortableHeader = ({
    label,
}: SortableHeaderOptions) => {
    return ({ column }: any) =>
        h(
            'button',
            {
                type: 'button',
                class: [
                    'flex w-full min-w-0 items-center gap-1.5 cursor-pointer',
                    'text-left text-sm font-medium',
                    'whitespace-normal break-words',
                    'leading-5',
                    'hover:text-foreground',
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
};
