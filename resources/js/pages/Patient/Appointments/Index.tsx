import PatientLayout from '@/layouts/patient-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Calendar } from 'lucide-react';
import { Link } from '@inertiajs/react';

interface Appointment {
    id: number;
    appointment_date: string;
    appointment_time: string;
    status: string;
    user?: {
        name: string;
    };
    exam_room?: {
        name: string;
    };
}

interface AppointmentsIndexProps {
    appointments?: Appointment[];
    filters?: {
        status?: string;
        upcoming?: boolean;
    };
}

export default function PatientAppointmentsIndex({
    appointments = [],
    filters = {},
}: AppointmentsIndexProps) {
    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'scheduled':
                return 'default';
            case 'completed':
                return 'secondary';
            case 'cancelled':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    return (
        <PatientLayout title="My Appointments">
            <Head title="My Appointments" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">My Appointments</h1>
                    <p className="mt-2 text-muted-foreground">
                        View and manage your appointments
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="size-5" />
                            Appointments
                        </CardTitle>
                        <CardDescription>
                            All your appointments
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {appointments.length > 0 ? (
                            <div className="space-y-4">
                                {appointments.map((appointment) => (
                                    <div
                                        key={appointment.id}
                                        className="flex items-center justify-between rounded-lg border p-4"
                                    >
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3">
                                                <p className="font-medium">
                                                    {new Date(appointment.appointment_date).toLocaleDateString()}
                                                </p>
                                                <Badge variant={getStatusBadgeVariant(appointment.status)}>
                                                    {appointment.status}
                                                </Badge>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {appointment.appointment_time}
                                            </p>
                                            {appointment.user && (
                                                <p className="text-sm text-muted-foreground">
                                                    Dr. {appointment.user.name}
                                                </p>
                                            )}
                                            {appointment.exam_room && (
                                                <p className="text-sm text-muted-foreground">
                                                    Room: {appointment.exam_room.name}
                                                </p>
                                            )}
                                        </div>
                                        <Link
                                            href={`/patient/appointments/${appointment.id}`}
                                            className="text-sm text-primary hover:underline"
                                        >
                                            View Details →
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No appointments found
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </PatientLayout>
    );
}

