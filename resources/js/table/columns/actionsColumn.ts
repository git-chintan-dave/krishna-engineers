import {Link} from '@inertiajs/vue3';
import {CheckCircle2, Eye, Pencil, RefreshCw, Trash2,} from '@lucide/vue';
import {type Component, h, type VNode,} from 'vue';

import {Button} from '@/components/ui/button';
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,} from '@/components/ui/tooltip';
import {executeConfirm} from '@/directives/confirm';
import ActionsDropdown from '@/table/ActionsDropdown.vue';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import type {ColumnPin} from '@/table/helpers/createColumn';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ActionsColumnOptions<TData = unknown> = {
    editUrl: (uuid: string) => string;
    resourceName: string;
    handleDelete: (uuid: string) => void;

    detailsUrl?: (uuid: string) => string;
    markAsPaidUrl?: (uuid: string) => string;
    regenerateUrl?: (uuid: string) => string;

    handleMarkAsPaid?: (uuid: string) => void;
    handleRegenerate?: (uuid: string) => void;

    showDelete?: (row: TData) => boolean;
    showEdit?: (row: TData) => boolean;
    showMarkAsPaid?: (row: TData) => boolean;
    showRegenerate?: (row: TData) => boolean;

    size?: number;
    minSize?: number;
    maxSize?: number;
    pin?: ColumnPin;
};

type ConfirmationOptions = {
    title: string;
    description: string;
    confirmText: string;
};

type SecondaryAction = {
    key: string;
    label: string;
    icon: Component;
    confirmation: ConfirmationOptions;
    action: () => void;
};

type ActionRecord = {
    uuid: string;
    name?: string;
    user?: {
        name?: string;
    };
};

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const ICON_CLASS = 'size-3';

const ICON_BUTTON_CLASS = [
    'size-6 shrink-0 cursor-pointer',
];

const ACTION_STYLES = {
    view: [
        'bg-slate-50 text-slate-600',
        'hover:bg-slate-100 hover:text-slate-700',
        'dark:bg-slate-950/40 dark:text-slate-400',
        'dark:hover:bg-slate-950/60 dark:hover:text-slate-300',
    ],

    edit: [
        'bg-blue-50 text-blue-600',
        'hover:bg-blue-100 hover:text-blue-700',
        'dark:bg-blue-950/40 dark:text-blue-400',
        'dark:hover:bg-blue-950/60 dark:hover:text-blue-300',
    ],

    delete: [
        'bg-red-50 text-red-600',
        'hover:bg-red-100 hover:text-red-700',
        'dark:bg-red-950/40 dark:text-red-400',
        'dark:hover:bg-red-950/60 dark:hover:text-red-300',
    ],
};

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function renderIcon(
    icon: Component,
    label: string,
): VNode[] {
    return [
        h(icon, {
            class: ICON_CLASS,
            'aria-hidden': 'true',
        }),

        h(
            'span',
            {
                class: 'sr-only',
            },
            label,
        ),
    ];
}

function createIconButton(
    icon: Component,
    label: string,
    colorClass: string[],
    options: {
        href?: string;
        disabled?: boolean;
        onClick?: () => void | Promise<void>;
    } = {},
): VNode {
    const button = h(
        Button,
        {
            type: 'button',
            variant: 'ghost',
            size: 'icon',
            class: [
                ICON_BUTTON_CLASS,
                ...colorClass,
            ],
            disabled: options.disabled,
            'aria-label': label,

            onClick: (event: MouseEvent) => {
                event.stopPropagation();

                if (!options.disabled) {
                    void options.onClick?.();
                }
            },

            ...(options.href
                ? {asChild: true}
                : {}),
        },

        options.href
            ? {
                default: () =>
                    h(
                        Link,
                        {
                            href: options.href!,
                        },
                        {
                            default: () =>
                                renderIcon(
                                    icon,
                                    label,
                                ),
                        },
                    ),
            }
            : {
                default: () =>
                    renderIcon(
                        icon,
                        label,
                    ),
            },
    );

    const trigger = options.disabled
        ? h(
            'span',
            {
                class: 'inline-flex',
                tabindex: 0,
                'aria-label': label,
            },
            [button],
        )
        : button;

    return h(
        TooltipProvider,
        {},
        {
            default: () =>
                h(
                    Tooltip,
                    {},
                    {
                        default: () => [
                            h(
                                TooltipTrigger,
                                {
                                    asChild: true,
                                },
                                {
                                    default: () =>
                                        trigger,
                                },
                            ),

                            h(
                                TooltipContent,
                                {
                                    side: 'top',
                                },
                                {
                                    default: () =>
                                        label,
                                },
                            ),
                        ],
                    },
                ),
        },
    );
}

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createActionsColumn = <
    TData = unknown,
>({
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
      pin,
  }: ActionsColumnOptions<TData>) => {
    const hasMarkAsPaid = Boolean(
        markAsPaidUrl &&
        handleMarkAsPaid,
    );

    const hasRegenerate = Boolean(
        regenerateUrl &&
        handleRegenerate,
    );

    return {
        id: 'actions',
        header: COLUMN_DEFAULTS.actions.header,
        size,
        minSize,
        maxSize,

        meta: {
            align: 'center' as const,

            ...(pin
                ? {pin}
                : {}),
        },

        enableSorting: false,

        cell: ({
                   row,
               }: {
            row: {
                original: TData;
            };
        }) => {
            const record =
                row.original as TData & ActionRecord;

            const {uuid} = record;

            const canEdit =
                showEdit?.(record) ?? true;

            const canDelete =
                showDelete?.(record) ?? true;

            const canMarkAsPaid =
                hasMarkAsPaid &&
                (showMarkAsPaid?.(record) ?? true);

            const canRegenerate =
                hasRegenerate &&
                (showRegenerate?.(record) ?? true);

            const primaryActions: VNode[] = [];

            // View action.
            if (detailsUrl) {
                primaryActions.push(
                    createIconButton(
                        Eye,
                        'View',
                        ACTION_STYLES.view,
                        {
                            href: detailsUrl(uuid),
                        },
                    ),
                );
            }

            // Edit action.
            if (canEdit) {
                primaryActions.push(
                    createIconButton(
                        Pencil,
                        'Edit',
                        ACTION_STYLES.edit,
                        {
                            href: editUrl(uuid),
                        },
                    ),
                );
            }

            // Delete action.
            primaryActions.push(
                createIconButton(
                    Trash2,
                    'Delete',
                    ACTION_STYLES.delete,
                    {
                        disabled: !canDelete,

                        onClick: canDelete
                            ? async () => {
                                await executeConfirm({
                                    action: () =>
                                        handleDelete(uuid),

                                    options: {
                                        title: `Delete ${resourceName}?`,
                                        description:
                                            'This action cannot be undone. The selected record will be permanently deleted.',
                                        confirmText: 'Delete',
                                    },
                                });
                            }
                            : undefined,
                    },
                ),
            );

            // Secondary actions.
            const secondaryActions: SecondaryAction[] = [];

            if (
                canRegenerate &&
                handleRegenerate
            ) {
                secondaryActions.push({
                    key: 'regenerate',
                    label: 'Regenerate',
                    icon: RefreshCw,

                    confirmation: {
                        title: `Regenerate ${resourceName}?`,
                        description:
                            `This will recalculate and update the existing ${resourceName}. ` +
                            'This action is only available for unpaid records.',
                        confirmText: 'Regenerate',
                    },

                    action: () =>
                        handleRegenerate(uuid),
                });
            }

            if (
                canMarkAsPaid &&
                handleMarkAsPaid
            ) {
                secondaryActions.push({
                    key: 'mark-as-paid',
                    label: 'Mark as Paid',
                    icon: CheckCircle2,

                    confirmation: {
                        title: `Mark ${resourceName} as paid?`,
                        description:
                            `This will mark the ${resourceName} as paid. ` +
                            'Paid records cannot be edited.',
                        confirmText: 'Mark as Paid',
                    },

                    action: () =>
                        handleMarkAsPaid(uuid),
                });
            }

            if (secondaryActions.length > 0) {
                if (primaryActions.length > 0) {
                    primaryActions.push(
                        h('span', {
                            class: 'mx-0.5 h-5 w-px shrink-0 bg-border',
                            'aria-hidden': 'true',
                        }),
                    );
                }

                primaryActions.push(
                    h(ActionsDropdown, {
                        name:
                            record.name ??
                            record.user?.name ??
                            'record',

                        actions: secondaryActions,
                    }),
                );
            }

            return h(
                'div',
                {
                    class: [
                        'flex h-8 min-h-8 items-center justify-center',
                        'gap-1 whitespace-nowrap',
                    ],
                },
                primaryActions,
            );
        },
    };
};
