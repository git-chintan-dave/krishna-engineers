import { COLUMN_WIDTHS } from '@/table/constants/columnWidths';
import { createColumn } from '@/table/helpers/createColumn';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type DateColumnOptions = {
    accessorKey: string;
    header?: string;
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createDateColumn = ({
    accessorKey,
    header = 'Date',
}: DateColumnOptions) =>
    createColumn({
        accessorKey,
        header,
        size: COLUMN_WIDTHS.date,
        sortable: true,
        align: 'center',
    });
