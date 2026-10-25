import {h} from 'vue';

import {Badge} from '@/components/ui/badge';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import {createColumn} from '@/table/helpers/createColumn';

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
                                       header = COLUMN_DEFAULTS.status.header,
                                       getVariant,
                                   }: StatusColumnOptions = {}) =>
    createColumn({
        accessorKey,
        header,
        size: COLUMN_DEFAULTS.status.size,
        minSize: COLUMN_DEFAULTS.status.minSize,
        maxSize: COLUMN_DEFAULTS.status.maxSize,
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
