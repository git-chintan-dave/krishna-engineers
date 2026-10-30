import {h} from 'vue';

import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import {createColumn} from '@/table/helpers/createColumn';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ImageColumnOptions<TData> = {
    getImage: (row: TData) => string | null;
    getAlt?: (row: TData) => string;
    header?: string;
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createImageColumn = <TData>({
                                             getImage,
                                             getAlt = () => 'Image',
                                             header = COLUMN_DEFAULTS.image.header,
                                         }: ImageColumnOptions<TData>) =>
    createColumn<TData, string | null>({
        id: 'image',
        accessorFn: (row) => getImage(row),
        header,
        size: COLUMN_DEFAULTS.image.size,
        minSize: COLUMN_DEFAULTS.image.minSize,
        maxSize: COLUMN_DEFAULTS.image.maxSize,
        cell: ({getValue, row}) => {
            const image = getValue();

            if (!image) {
                return h(
                    'span',
                    {class: 'text-sm font-normal text-muted-foreground'},
                    'N/A',
                );
            }

            return h('img', {
                src: image,
                alt: getAlt(row.original),
                class: ['size-10 shrink-0 rounded-md border object-cover'],
                loading: 'lazy',
                decoding: 'async',
            });
        },
    });
