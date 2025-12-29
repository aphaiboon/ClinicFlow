import { AppointmentForm } from '@/components/appointments/AppointmentForm';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/appointments';
import {
    type BreadcrumbItem,
    type ExamRoom,
    type Patient,
    type User,
} from '@/types';
import { Head } from '@inertiajs/react';

interface AppointmentsCreateProps {
    patients: Patient[];
    clinicians: User[];
    examRooms: ExamRoom[];
    preselectedDate?: string;
    preselectedTime?: string;
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

export default function Create({
    patients,
    clinicians,
    examRooms,
    preselectedDate,
    preselectedTime,
}: AppointmentsCreateProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schedule Appointment" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Schedule Appointment
                    </h1>
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
                        preselectedDate={preselectedDate}
                        preselectedTime={preselectedTime}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
