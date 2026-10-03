import { dashboard } from '@/routes';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type Breadcrumb = {
    title: string;
    href?: string;
};

type CrudBreadcrumbOptions = {
    resource: string;
    resourceUrl: string;
};

type CrudPage =
    | 'listing'
    | 'create'
    | 'edit'
    | 'details'
    | 'import';

/*
|--------------------------------------------------------------------------
| Page Titles
|--------------------------------------------------------------------------
*/

const pageTitles: Record<Exclude<CrudPage, 'listing'>, string> = {
    create: 'Create',
    edit: 'Edit',
    details: 'Details',
    import: 'Import',
};

/*
|--------------------------------------------------------------------------
| Breadcrumbs
|--------------------------------------------------------------------------
*/

export const useCrudBreadcrumbs = (
    page: CrudPage,
    options: CrudBreadcrumbOptions,
): Breadcrumb[] => {
    const breadcrumbs: Breadcrumb[] = [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: options.resource,
        },
    ];

    if (page === 'listing') {
        return breadcrumbs;
    }

    breadcrumbs[1].href = options.resourceUrl;

    breadcrumbs.push({
        title: pageTitles[page],
    });

    return breadcrumbs;
};
