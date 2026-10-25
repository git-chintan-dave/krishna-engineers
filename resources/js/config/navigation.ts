import {
    ArrowLeftRight,
    ClipboardCheck,
    ClipboardList,
    Clock3,
    Cog,
    Database,
    Grid3X3,
    Layers3,
    LayoutGrid,
    Package,
    Ruler,
    Scissors,
    Settings2,
    Users,
    WalletCards,
    Workflow,
    Wrench,
} from '@lucide/vue';

import {dashboard} from '@/routes';
import {index as machines} from '@/routes/machines';
import {index as setups} from '@/routes/setups';
import {index as parts} from '@/routes/parts';
import {index as partSetups} from '@/routes/part-setups';
import {index as sections} from '@/routes/sections';
import {index as sectionSizes} from '@/routes/section-sizes';
import {index as tools} from '@/routes/tools';
import {index as rawMaterialTransactions} from '@/routes/raw-material-transactions';
import {index as toolTransactions} from '@/routes/tool-transactions';
import {index as employees} from '@/routes/employees';
import {index as attendance} from '@/routes/attendances';
import {index as salaries} from '@/routes/salaries';
import {index as operatorWorkLogs} from '@/routes/operator';
import {index as bandsawWorkLogs} from '@/routes/bandsaw';

import type {NavItem} from '@/types';

const dashboardItem: NavItem = {
    title: 'Dashboard',
    description: 'Overview of your workshop.',
    href: dashboard(),
    icon: LayoutGrid,
};

const masterDataItems: NavItem[] = [
    {
        title: 'Machines',
        description: 'Manage workshop machines.',
        href: machines(),
        icon: Cog,
    },
    {
        title: 'Setups',
        description: 'Manage machine setups.',
        href: setups(),
        icon: Settings2,
    },
    {
        title: 'Parts',
        description: 'Manage production parts.',
        href: parts(),
        icon: Package,
    },
    {
        title: 'Part Setups',
        description: 'Configure parts for production setups.',
        href: partSetups(),
        icon: Workflow,
    },
    {
        title: 'Sections',
        description: 'Manage material sections.',
        href: sections(),
        icon: Grid3X3,
    },
    {
        title: 'Section Sizes',
        description: 'Manage section dimensions and sizes.',
        href: sectionSizes(),
        icon: Ruler,
    },
    {
        title: 'Tools',
        description: 'Manage production tools.',
        href: tools(),
        icon: Wrench,
    },
];

const inventoryItems: NavItem[] = [
    {
        title: 'Raw Material Transactions',
        description: 'Track raw material movements.',
        href: rawMaterialTransactions(),
        icon: ArrowLeftRight,
    },
    {
        title: 'Tool Transactions',
        description: 'Track tool movements and usage.',
        href: toolTransactions(),
        icon: ArrowLeftRight,
    },
];

const workLogItems: NavItem[] = [
    {
        title: 'Operator',
        description: 'Record and review operator production.',
        href: operatorWorkLogs(),
        icon: ClipboardCheck,
    },
    {
        title: 'Bandsaw',
        description: 'Record and review bandsaw work.',
        href: bandsawWorkLogs(),
        icon: Scissors,
    },
];

const peopleItems: NavItem[] = [
    {
        title: 'Employees',
        description: 'Manage workshop employees.',
        href: employees(),
        icon: Users,
    },
    {
        title: 'Attendance',
        description: 'Review employee attendance.',
        href: attendance(),
        icon: Clock3,
    },
];

const salaryItem: NavItem = {
    title: 'Salaries',
    description: 'Review and manage employee salaries.',
    href: salaries(),
    icon: WalletCards,
};

const masterDataNav: NavItem = {
    title: 'Master Data',
    description: 'Manage production configuration.',
    icon: Database,
    items: masterDataItems,
};

const inventoryNav: NavItem = {
    title: 'Inventory',
    description: 'Manage material and tool transactions.',
    icon: Layers3,
    items: inventoryItems,
};

const workLogsNav: NavItem = {
    title: 'Work Logs',
    description: 'Review and record production work.',
    icon: ClipboardList,
    items: workLogItems,
};

const peopleNav: NavItem = {
    title: 'People',
    description: 'Manage employees and attendance.',
    icon: Users,
    items: peopleItems,
};

export function getMainNavItems(role?: string): NavItem[] {
    switch (role) {
        case 'operator':
            return [
                dashboardItem,
                {
                    title: 'Operator Work Logs',
                    description: 'Record and review your production.',
                    href: operatorWorkLogs(),
                    icon: ClipboardCheck,
                },
                {
                    title: 'Attendance',
                    description: 'View your attendance.',
                    href: attendance(),
                    icon: Clock3,
                },
            ];

        case 'bandsaw':
            return [
                dashboardItem,
                {
                    title: 'Bandsaw Work Logs',
                    description: 'Record and review your bandsaw work.',
                    href: bandsawWorkLogs(),
                    icon: Scissors,
                },
                {
                    title: 'Attendance',
                    description: 'View your attendance.',
                    href: attendance(),
                    icon: Clock3,
                },
            ];

        case 'supervisor':
            return [dashboardItem, workLogsNav, peopleNav];

        case 'super_admin':
        default:
            return [
                dashboardItem,
                masterDataNav,
                inventoryNav,
                workLogsNav,
                {
                    ...peopleNav,
                    items: [...peopleItems, salaryItem],
                },
            ];
    }
}

/**
 * Returns only direct navigation links for dashboard Quick Actions.
 * Group headings are excluded; their child links are included.
 */
export function getDashboardQuickActions(role?: string) {
    return getMainNavItems(role).flatMap((item) =>
        (item.items ? item.items : [item]).flatMap((child) => {
            if (!child.href || child.title === 'Dashboard') {
                return [];
            }

            return [
                {
                    label: child.title,
                    description: child.description ?? '',
                    href:
                        typeof child.href === 'string'
                            ? child.href
                            : child.href.url,
                    icon: child.icon?.name ?? 'ClipboardList',
                },
            ];
        }),
    );
}

export const rightNavItems: NavItem[] = [];
