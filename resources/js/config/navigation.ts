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

import { index as rawMaterial } from '@/routes/raw-material';
import { index as tools } from '@/routes/tools';

import { index as rawMaterialTransactions } from '@/routes/raw-material-transactions';
import { index as toolTransactions } from '@/routes/tool-transactions';

import { index as employees } from '@/routes/employees';
import { index as attendance } from '@/routes/attendances';
import { index as salaries } from '@/routes/salaries';

import { index as operatorWorkLogs } from '@/routes/operator';
import { index as bandsawWorkLogs } from '@/routes/bandsaw';

import type { NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },

    {
        title: 'Master Data',
        icon: Database,
        items: [
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
            // {
            //     title: 'Raw Material',
            //     href: rawMaterial(),
            //     icon: Layers3,
            // },
            {
                title: 'Tools',
                href: tools(),
                icon: Wrench,
            },
        ],
    },

    {
        title: 'Inventory',
        icon: Layers3,
        items: [
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
        ],
    },

    {
        title: 'Work Logs',
        icon: ClipboardList,
        items: [
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
        ],
    },

    {
        title: 'People',
        icon: Users,
        items: [
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
            {
                title: 'Salaries',
                href: salaries(),
                icon: WalletCards,
            },
        ],
    },
];

export const rightNavItems: NavItem[] = [];
