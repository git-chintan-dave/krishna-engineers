import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import {type ColumnPin, createColumn} from '@/table/helpers/createColumn';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type DateColumnOptions = {
    accessorKey?: string;
    header?: string;
    pin?: ColumnPin;
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createDateColumn = ({
                                     accessorKey = 'created_at',
                                     header = COLUMN_DEFAULTS.date.header,
                                     pin,
                                 }: DateColumnOptions = {}) =>
    createColumn({
        accessorKey,
        header,
        size: COLUMN_DEFAULTS.date.size,
        minSize: COLUMN_DEFAULTS.date.minSize,
        maxSize: COLUMN_DEFAULTS.date.maxSize,
        sortable: true,
        align: 'center',
        pin,
    });
