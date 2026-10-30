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
| Helpers
|--------------------------------------------------------------------------
*/

const getStatusClass = (status: string): string => {
    switch (status.toLowerCase()) {
        case 'active':
        case 'success':
        case 'completed':
        case 'approved':
        case 'paid':
            return [
                'border-emerald-200/80 bg-emerald-500/[0.06] text-emerald-700',
                'dark:border-emerald-800/70 dark:bg-emerald-400/[0.08] dark:text-emerald-300',
            ].join(' ');

        case 'inactive':
        case 'disabled':
        case 'archived':
            return [
                'border-slate-200/80 bg-slate-500/[0.06] text-slate-600',
                'dark:border-slate-700/70 dark:bg-slate-400/[0.08] dark:text-slate-300',
            ].join(' ');

        case 'pending':
        case 'processing':
        case 'in_progress':
            return [
                'border-blue-200/80 bg-blue-500/[0.06] text-blue-700',
                'dark:border-blue-800/70 dark:bg-blue-400/[0.08] dark:text-blue-300',
            ].join(' ');

        case 'draft':
            return [
                'border-violet-200/80 bg-violet-500/[0.06] text-violet-700',
                'dark:border-violet-800/70 dark:bg-violet-400/[0.08] dark:text-violet-300',
            ].join(' ');

        case 'warning':
        case 'overdue':
        case 'late':
        case 'partial':
            return [
                'border-amber-200/80 bg-amber-500/[0.06] text-amber-700',
                'dark:border-amber-800/70 dark:bg-amber-400/[0.08] dark:text-amber-300',
            ].join(' ');

        case 'cancelled':
        case 'canceled':
        case 'rejected':
        case 'failed':
        case 'error':
            return [
                'border-red-200/80 bg-red-500/[0.06] text-red-700',
                'dark:border-red-800/70 dark:bg-red-400/[0.08] dark:text-red-300',
            ].join(' ');

        case 'new':
        case 'info':
            return [
                'border-cyan-200/80 bg-cyan-500/[0.06] text-cyan-700',
                'dark:border-cyan-800/70 dark:bg-cyan-400/[0.08] dark:text-cyan-300',
            ].join(' ');

        default:
            return [
                'border-border/70 bg-muted/50 text-foreground',
                'dark:bg-muted/40',
            ].join(' ');
    }
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
        cell: ({getValue}) => {
            const status = (getValue() as string | null | undefined) ?? '';

            if (!status) {
                return '—';
            }

            const variant = getVariant
                ? getVariant(status)
                : status === 'active'
                    ? 'default'
                    : 'secondary';

            return h(
                Badge,
                {
                    variant,
                    class: [
                        'border px-2 py-0.5 text-xs! font-medium! capitalize shadow-none',
                        'transition-colors',
                        getStatusClass(status),
                    ],
                },
                () => status.replace(/_/g, ' '),
            );
        },
    });
