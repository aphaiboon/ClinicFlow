import { PatientForm } from '@/components/patients/PatientForm';
import AppLayout from '@/layouts/app-layout';
import { index, show, update } from '@/routes/patients';
import { type BreadcrumbItem, type Patient } from '@/types';
import { Head } from '@inertiajs/react';

interface PatientsEditProps {
    patient: Patient;
}

const breadcrumbs = (patient: Patient): BreadcrumbItem[] => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Patients',
        href: index().url,
    },
    {
        title: `${patient.first_name} ${patient.last_name}`,
        href: show(patient.id).url,
    },
    {
        title: 'Edit',
        href: `/patients/${patient.id}/edit`,
    },
];

export default function Edit({ patient }: PatientsEditProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs(patient)}>
            <Head title={`Edit ${patient.first_name} ${patient.last_name}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Edit {patient.first_name} {patient.last_name}
                    </h1>
                    <p className="text-muted-foreground">
                        Update patient information
                    </p>
                </div>

                <div className="max-w-2xl">
                    <PatientForm patient={patient} route={update(patient.id)} />
                </div>
            </div>
        </AppLayout>
    );
}
