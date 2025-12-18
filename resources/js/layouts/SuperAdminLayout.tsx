import { Head } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';
import AppSidebarLayout from './app/app-sidebar-layout';
import { dashboard as superAdminDashboard } from '@/routes/super-admin';

interface SuperAdminLayoutProps {
    title?: string;
    breadcrumbs?: Array<{ label: string; href?: string }>;
}

export default function SuperAdminLayout({
    children,
    title,
    breadcrumbs = [],
}: PropsWithChildren<SuperAdminLayoutProps>) {
    const fullBreadcrumbs = [
        { label: 'Super Admin', href: superAdminDashboard() },
        ...breadcrumbs,
    ];

    return (
        <AppSidebarLayout breadcrumbs={fullBreadcrumbs}>
            {title && <Head title={title} />}
            <div className="container mx-auto py-6">{children}</div>
        </AppSidebarLayout>
    );
}

