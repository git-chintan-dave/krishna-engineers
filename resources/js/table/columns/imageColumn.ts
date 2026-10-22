import { h } from 'vue';

import { createColumn } from '@/table/helpers/createColumn';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ImageColumnOptions<TData> = {
    getImage: (row: TData) => string | null;
    getAlt?: (row: TData) => string;
    title?: string;
    size?: number;
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createImageColumn = <TData>({
    getImage,
    getAlt = () => 'Image',
    title = 'Image',
    size = 100,
}: ImageColumnOptions<TData>) =>
    createColumn<TData, string | null>({
        id: 'image',
        accessorFn: (row) => getImage(row),
        header: title,
        size,
        cell: ({ getValue, row }) => {
            const image = getValue();

            if (!image) {
                return h(
                    'span',
                    { class: 'text-sm font-normal text-muted-foreground' },
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
