import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
} from '@lucide/vue';
import { h } from 'vue';

import { Button } from '@/components/ui/button';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type DateColumnOptions = {
    accessorKey: string;
    header?: string;
    size?: number;
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createDateColumn = ({
                                     accessorKey,
                                     header = 'Date',
                                     size = 140,
                                 }: DateColumnOptions) => ({
    accessorKey,

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    header: ({ column }: any) => {
        const sorted = column.getIsSorted();

        const Icon =
            sorted === 'asc'
                ? ArrowUp
                : sorted === 'desc'
                    ? ArrowDown
                    : ArrowUpDown;

        return h(
            Button,
            {
                variant: 'ghost',
                class: 'h-8 cursor-pointer px-2',
                onClick: () =>
                    column.toggleSorting(
                        sorted === 'asc',
                    ),
            },
            () => [
                h(
                    'span',
                    {
                        class: 'whitespace-nowrap',
                    },
                    header,
                ),

                h(Icon, {
                    class: 'ml-1 size-4 shrink-0',
                    'aria-hidden': 'true',
                }),
            ],
        );
    },

    /*
    |--------------------------------------------------------------------------
    | Width
    |--------------------------------------------------------------------------
    */

    size,
    minSize: size,
    maxSize: size,
});
