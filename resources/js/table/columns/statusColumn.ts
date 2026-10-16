
import { h } from 'vue';

import { Badge } from '@/components/ui/badge';
import { createColumn } from '@/table/helpers/createColumn';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type StatusColumnOptions = {
    accessorKey?: string;
    header?: string;
    size?: number;
    getVariant?: (
        status: string,
    ) => 'default' | 'secondary' | 'destructive' | 'outline';
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createStatusColumn = ({
                                       accessorKey = 'status',
                                       header = 'Status',
                                       size = 120,
                                       getVariant,
                                   }: StatusColumnOptions = {}) =>
    createColumn({
        accessorKey,
        header,
        size,
        enableSorting: false,
        cell: ({ getValue }) => {
            const status = (getValue() as string | null | undefined) ?? '';

            const variant = getVariant
                ? getVariant(status)
                : status === 'active'
                    ? 'default'
                    : 'secondary';

            return h(
                Badge,
                {
                    variant,
                    class: 'text-sm! font-normal! capitalize',
                },
                () => status || '—',
            );
        },
    });
