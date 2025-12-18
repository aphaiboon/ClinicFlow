import { PatientForm } from '@/components/patients/PatientForm';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/patients';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Patients',
        href: index().url,
    },
    {
        title: 'Create',
        href: store().url,
    },
];

export default function Create() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Patient" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Create Patient
                    </h1>
                    <p className="text-muted-foreground">
                        Add a new patient to the system
                    </p>
                </div>

                <div className="max-w-2xl">
                    <PatientForm route={store()} />
                </div>
            </div>
        </AppLayout>
    );
}
