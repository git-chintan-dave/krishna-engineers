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

import { dashboard } from '@/routes';
import { index as machines } from '@/routes/machines';
import { index as setups } from '@/routes/setups';
import { index as parts } from '@/routes/parts';
import { index as partSetups } from '@/routes/part-setups';
import { index as sections } from '@/routes/sections';
import { index as sectionSizes } from '@/routes/section-sizes';
import { index as tools } from '@/routes/tools';
import { index as rawMaterialTransactions } from '@/routes/raw-material-transactions';
import { index as toolTransactions } from '@/routes/tool-transactions';
import { index as employees } from '@/routes/employees';
import { index as attendance } from '@/routes/attendances';
import { index as salaries } from '@/routes/salaries';
import { index as operatorWorkLogs } from '@/routes/operator';
import { index as bandsawWorkLogs } from '@/routes/bandsaw';

import type { NavItem } from '@/types';

const dashboardItem: NavItem = {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
};

const masterDataItems: NavItem[] = [
    {
        title: 'Machines',
        href: machines(),
        icon: Cog,
    },
    {
        title: 'Setups',
        href: setups(),
        icon: Settings2,
    },
    {
        title: 'Parts',
        href: parts(),
        icon: Package,
    },
    {
        title: 'Part Setups',
        href: partSetups(),
        icon: Workflow,
    },
    {
        title: 'Sections',
        href: sections(),
        icon: Grid3X3,
    },
    {
        title: 'Section Sizes',
        href: sectionSizes(),
        icon: Ruler,
    },
    {
        title: 'Tools',
        href: tools(),
        icon: Wrench,
    },
];

const inventoryItems: NavItem[] = [
    {
        title: 'Raw Material Transactions',
        href: rawMaterialTransactions(),
        icon: ArrowLeftRight,
    },
    {
        title: 'Tool Transactions',
        href: toolTransactions(),
        icon: ArrowLeftRight,
    },
];

const workLogItems: NavItem[] = [
    {
        title: 'Operator',
        href: operatorWorkLogs(),
        icon: ClipboardCheck,
    },
    {
        title: 'Bandsaw',
        href: bandsawWorkLogs(),
        icon: Scissors,
    },
];

const peopleItems: NavItem[] = [
    {
        title: 'Employees',
        href: employees(),
        icon: Users,
    },
    {
        title: 'Attendance',
        href: attendance(),
        icon: Clock3,
    },
];

const salaryItem: NavItem = {
    title: 'Salaries',
    href: salaries(),
    icon: WalletCards,
};

const masterDataNav: NavItem = {
    title: 'Master Data',
    icon: Database,
    items: masterDataItems,
};

const inventoryNav: NavItem = {
    title: 'Inventory',
    icon: Layers3,
    items: inventoryItems,
};

const workLogsNav: NavItem = {
    title: 'Work Logs',
    icon: ClipboardList,
    items: workLogItems,
};

const peopleNav: NavItem = {
    title: 'People',
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
                    href: operatorWorkLogs(),
                    icon: ClipboardCheck,
                },
                {
                    title: 'Attendance',
                    href: attendance(),
                    icon: Clock3,
                },
            ];

        case 'bandsaw':
            return [
                dashboardItem,
                {
                    title: 'Bandsaw Work Logs',
                    href: bandsawWorkLogs(),
                    icon: Scissors,
                },
                {
                    title: 'Attendance',
                    href: attendance(),
                    icon: Clock3,
                },
            ];

        case 'supervisor':
            return [
                dashboardItem,
                workLogsNav,
                peopleNav,
            ];

        case 'super_admin':
        default:
            return [
                dashboardItem,
                masterDataNav,
                inventoryNav,
                workLogsNav,
                {
                    ...peopleNav,
                    items: [
                        ...peopleItems,
                        salaryItem,
                    ],
                },
            ];
    }
}

export const rightNavItems: NavItem[] = [];
