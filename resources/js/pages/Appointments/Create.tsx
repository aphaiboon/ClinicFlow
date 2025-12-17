import { AppointmentForm } from '@/components/appointments/AppointmentForm';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Patient, type User, type ExamRoom } from '@/types';
import { Head } from '@inertiajs/react';
import { store, index } from '@/routes/appointments';

interface AppointmentsCreateProps {
    patients: Patient[];
    clinicians: User[];
    examRooms: ExamRoom[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Appointments',
        href: index().url,
    },
    {
        title: 'Create',
        href: store().url,
    },
];

export default function Create({ patients, clinicians, examRooms }: AppointmentsCreateProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schedule Appointment" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Schedule Appointment</h1>
                    <p className="text-muted-foreground">
                        Create a new appointment
                    </p>
                </div>

                <div className="max-w-2xl">
                    <AppointmentForm
                        route={store()}
                        patients={patients}
                        clinicians={clinicians}
                        examRooms={examRooms}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
