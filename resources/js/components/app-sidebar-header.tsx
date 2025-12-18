import { Breadcrumbs } from '@/components/breadcrumbs';
import OrganizationSwitcher from '@/components/OrganizationSwitcher';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            {auth.user && auth.organizations && auth.organizations.length > 1 && (
                <div className="ml-auto">
                    <OrganizationSwitcher
                        organizations={auth.organizations}
                        currentOrganization={auth.currentOrganization}
                    />
                </div>
            )}
        </header>
    );
}
