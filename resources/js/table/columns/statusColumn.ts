import { h } from 'vue';

import { Badge } from '@/components/ui/badge';
import { COLUMN_WIDTHS } from '@/table/constants/columnWidths';
import { createColumn } from '@/table/helpers/createColumn';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type StatusColumnOptions = {
    accessorKey?: string;
    header?: string;
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
    getVariant,
}: StatusColumnOptions = {}) =>
    createColumn({
        accessorKey,
        header,
        size: COLUMN_WIDTHS.status,
        minSize: COLUMN_WIDTHS.status,
        maxSize: COLUMN_WIDTHS.status,
        align: 'center',
        sortable: true,
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
