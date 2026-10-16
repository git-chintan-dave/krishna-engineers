import {h} from 'vue';

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
                                         }: ImageColumnOptions<TData>) => ({
    id: 'image',

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    header: title,

    /*
    |--------------------------------------------------------------------------
    | Cell
    |--------------------------------------------------------------------------
    */

    cell: ({row}: any) => {
        const data = row.original as TData;
        const image = getImage(data);

        if (!image) {
            return h(
                'span',
                {
                    class: 'text-sm text-muted-foreground',
                },
                'N/A',
            );
        }

        return h('img', {
            src: image,
            alt: getAlt(data),

            class: ['size-10 shrink-0', 'rounded-md border', 'object-cover'],

            loading: 'lazy',
            decoding: 'async',
        });
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
