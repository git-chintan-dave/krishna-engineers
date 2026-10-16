import {createColumn} from '@/table/helpers/createColumn';

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
                                 }: DateColumnOptions) =>
    createColumn({
        accessorKey,
        header,
        size,
        sortable: true,
    });
