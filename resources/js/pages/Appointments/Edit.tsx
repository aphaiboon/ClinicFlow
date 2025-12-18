import { AppointmentForm } from '@/components/appointments/AppointmentForm';
import AppLayout from '@/layouts/app-layout';
import { index, show, update } from '@/routes/appointments';
import {
    type Appointment,
    type BreadcrumbItem,
    type ExamRoom,
    type Patient,
    type User,
} from '@/types';
import { Head } from '@inertiajs/react';

interface AppointmentsEditProps {
    appointment: Appointment;
    patients: Patient[];
    clinicians: User[];
    examRooms: ExamRoom[];
}

const breadcrumbs = (appointment: Appointment): BreadcrumbItem[] => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Appointments',
        href: index().url,
    },
    {
        title: `Appointment #${appointment.id}`,
        href: show(appointment.id).url,
    },
    {
        title: 'Edit',
        href: `/appointments/${appointment.id}/edit`,
    },
];

export default function Edit({
    appointment,
    patients,
    clinicians,
    examRooms,
}: AppointmentsEditProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs(appointment)}>
            <Head title={`Edit Appointment #${appointment.id}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Edit Appointment #{appointment.id}
                    </h1>
                    <p className="text-muted-foreground">
                        Update appointment information
                    </p>
                </div>

                <div className="max-w-2xl">
                    <AppointmentForm
                        appointment={appointment}
                        route={update(appointment.id)}
                        patients={patients}
                        clinicians={clinicians}
                        examRooms={examRooms}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
