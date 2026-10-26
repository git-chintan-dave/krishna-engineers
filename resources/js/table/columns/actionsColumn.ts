import {Link} from '@inertiajs/vue3';
import {CheckCircle2, Eye, Pencil, RefreshCw, Trash2} from '@lucide/vue';
import {h, withDirectives} from 'vue';

import {Button} from '@/components/ui/button';
import vConfirm from '@/directives/confirm';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ActionsColumnOptions = {
    editUrl: (uuid: string) => string;
    detailsUrl?: (uuid: string) => string;
    markAsPaidUrl?: (uuid: string) => string;
    regenerateUrl?: (uuid: string) => string;
    resourceName: string;
    handleDelete: (uuid: string) => void;
    handleMarkAsPaid?: (uuid: string) => void;
    handleRegenerate?: (uuid: string) => void;
    showDelete?: (row: any) => boolean;
    showEdit?: (row: any) => boolean;
    showMarkAsPaid?: (row: any) => boolean;
    showRegenerate?: (row: any) => boolean;
    size?: number;
    minSize?: number;
    maxSize?: number;
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createActionsColumn = ({
                                        editUrl,
                                        detailsUrl,
                                        markAsPaidUrl,
                                        regenerateUrl,
                                        resourceName,
                                        handleDelete,
                                        handleMarkAsPaid,
                                        handleRegenerate,
                                        showDelete,
                                        showEdit,
                                        showMarkAsPaid,
                                        showRegenerate,
                                        size = COLUMN_DEFAULTS.actions.size,
                                        minSize = COLUMN_DEFAULTS.actions.minSize,
                                        maxSize = COLUMN_DEFAULTS.actions.maxSize,
                                    }: ActionsColumnOptions) => {
    const hasMarkAsPaid = Boolean(markAsPaidUrl && handleMarkAsPaid);
    const hasRegenerate = Boolean(regenerateUrl && handleRegenerate);

    return {
        id: 'actions',
        header: COLUMN_DEFAULTS.actions.header,
        size,
        minSize,
        maxSize,
        meta: {
            align: 'center',
        },
        enableSorting: false,
        cell: ({row}: any) => {
            const uuid = row.original.uuid;
            const name =
                row.original.name ?? row.original.user?.name ?? 'record';

            const actions = [];

            const canEdit = showEdit?.(row.original) ?? true;
            const canDelete = showDelete?.(row.original) ?? true;
            const canMarkAsPaid =
                hasMarkAsPaid && (showMarkAsPaid?.(row.original) ?? true);
            const canRegenerate =
                hasRegenerate && (showRegenerate?.(row.original) ?? true);

            if (detailsUrl) {
                actions.push(
                    h(
                        Button,
                        {
                            variant: 'ghost',
                            size: 'icon',
                            class: [
                                'size-8 shrink-0 cursor-pointer',
                                'bg-slate-50 text-slate-600',
                                'hover:bg-slate-100 hover:text-slate-700',
                                'dark:bg-slate-950/40 dark:text-slate-400',
                                'dark:hover:bg-slate-950/60',
                                'dark:hover:text-slate-300',
                            ],
                            asChild: true,
                        },
                        () =>
                            h(Link, {href: detailsUrl(uuid)}, () => [
                                h(Eye, {
                                    class: 'size-3.5',
                                    'aria-hidden': 'true',
                                }),
                                h('span', {class: 'sr-only'}, `View ${name}`),
                            ]),
                    ),
                );
            }

            if (canEdit) {
                actions.push(
                    h(
                        Button,
                        {
                            variant: 'ghost',
                            size: 'icon',
                            class: [
                                'size-8 shrink-0 cursor-pointer',
                                'bg-blue-50 text-blue-600',
                                'hover:bg-blue-100 hover:text-blue-700',
                                'dark:bg-blue-950/40 dark:text-blue-400',
                                'dark:hover:bg-blue-950/60',
                                'dark:hover:text-blue-300',
                            ],
                            asChild: true,
                        },
                        () =>
                            h(Link, {href: editUrl(uuid)}, () => [
                                h(Pencil, {
                                    class: 'size-3.5',
                                    'aria-hidden': 'true',
                                }),
                                h('span', {class: 'sr-only'}, `Edit ${name}`),
                            ]),
                    ),
                );
            }

            if (canRegenerate && regenerateUrl && handleRegenerate) {
                actions.push(
                    withDirectives(
                        h(
                            Button,
                            {
                                variant: 'ghost',
                                size: 'icon',
                                class: [
                                    'size-8 shrink-0 cursor-pointer',
                                    'bg-amber-50 text-amber-600',
                                    'hover:bg-amber-100 hover:text-amber-700',
                                    'dark:bg-amber-950/40 dark:text-amber-400',
                                    'dark:hover:bg-amber-950/60',
                                    'dark:hover:text-amber-300',
                                ],
                            },
                            () => [
                                h(RefreshCw, {
                                    class: 'size-3.5',
                                    'aria-hidden': 'true',
                                }),
                                h(
                                    'span',
                                    {class: 'sr-only'},
                                    `Regenerate ${name}`,
                                ),
                            ],
                        ),
                        [
                            [
                                vConfirm,
                                {
                                    action: () => handleRegenerate(uuid),
                                    options: {
                                        title: `Regenerate ${resourceName}?`,
                                        description:
                                            `This will recalculate and update the existing ${resourceName}. ` +
                                            'This action is only available for unpaid records.',
                                        confirmText: 'Regenerate',
                                    },
                                },
                            ],
                        ],
                    ),
                );
            }

            if (canMarkAsPaid && markAsPaidUrl && handleMarkAsPaid) {
                actions.push(
                    withDirectives(
                        h(
                            Button,
                            {
                                variant: 'ghost',
                                size: 'icon',
                                class: [
                                    'size-8 shrink-0 cursor-pointer',
                                    'bg-emerald-50 text-emerald-600',
                                    'hover:bg-emerald-100 hover:text-emerald-700',
                                    'dark:bg-emerald-950/40 dark:text-emerald-400',
                                    'dark:hover:bg-emerald-950/60',
                                    'dark:hover:text-emerald-300',
                                ],
                            },
                            () => [
                                h(CheckCircle2, {
                                    class: 'size-3.5',
                                    'aria-hidden': 'true',
                                }),
                                h(
                                    'span',
                                    {class: 'sr-only'},
                                    `Mark ${name} as paid`,
                                ),
                            ],
                        ),
                        [
                            [
                                vConfirm,
                                {
                                    action: () => handleMarkAsPaid(uuid),
                                    options: {
                                        title: `Mark ${resourceName} as paid?`,
                                        description:
                                            `This will mark the ${resourceName} as paid. ` +
                                            'Paid records cannot be edited.',
                                        confirmText: 'Mark as Paid',
                                    },
                                },
                            ],
                        ],
                    ),
                );
            }

            if (canDelete) {
                actions.push(
                    withDirectives(
                        h(
                            Button,
                            {
                                variant: 'ghost',
                                size: 'icon',
                                class: [
                                    'size-8 shrink-0 cursor-pointer',
                                    'bg-red-50 text-red-600',
                                    'hover:bg-red-100 hover:text-red-700',
                                    'dark:bg-red-950/40 dark:text-red-400',
                                    'dark:hover:bg-red-950/60',
                                ],
                            },
                            () => [
                                h(Trash2, {
                                    class: 'size-3.5',
                                    'aria-hidden': 'true',
                                }),
                                h(
                                    'span',
                                    {class: 'sr-only'},
                                    `Delete ${name}`,
                                ),
                            ],
                        ),
                        [
                            [
                                vConfirm,
                                {
                                    action: () => handleDelete(uuid),
                                    options: {
                                        title: `Delete ${resourceName}?`,
                                        description:
                                            'This action cannot be undone. ' +
                                            `The ${resourceName} will be permanently deleted.`,
                                        confirmText: 'Delete',
                                    },
                                },
                            ],
                        ],
                    ),
                );
            }

            return h(
                'div',
                {
                    class: [
                        'flex items-center justify-center',
                        'gap-1 whitespace-nowrap text-sm',
                    ],
                },
                actions,
            );
        },
    };
};
