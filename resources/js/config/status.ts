import type { SelectOption } from '@/types/common';
import type { StatusFilter } from '@/types/listing';

export const statusOptions: SelectOption<StatusFilter>[] = [
    {
        value: 'all',
        label: 'All',
    },
    {
        value: 'active',
        label: 'Active',
    },
    {
        value: 'inactive',
        label: 'Inactive',
    },
];
