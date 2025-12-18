import PatientLayout from '@/layouts/patient-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, Clock, User, MapPin } from 'lucide-react';
import { useState } from 'react';

interface Appointment {
    id: number;
    appointment_date: string;
    appointment_time: string;
    status: string;
    duration_minutes: number;
    appointment_type: string;
    notes?: string;
    user?: {
        name: string;
    };
    exam_room?: {
        name: string;
    };
}

interface AppointmentShowProps {
    appointment: Appointment;
    canCancel?: boolean;
}

export default function PatientAppointmentShow({
    appointment,
    canCancel = false,
}: AppointmentShowProps) {
    const [isCancelling, setIsCancelling] = useState(false);

    const handleCancel = () => {
        if (confirm('Are you sure you want to cancel this appointment?')) {
            setIsCancelling(true);
            router.post(`/patient/appointments/${appointment.id}/cancel`, {
                reason: 'Cancelled by patient',
            });
        }
    };

    return (
        <PatientLayout title="Appointment Details">
            <Head title="Appointment Details" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Appointment Details</h1>
                        <p className="mt-2 text-muted-foreground">
                            View your appointment information
                        </p>
                    </div>
                    <Link
                        href="/patient/appointments"
                        className="text-sm text-primary hover:underline"
                    >
                        ← Back to Appointments
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="size-5" />
                            Appointment Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Date</p>
                                <p className="text-lg font-semibold">
                                    {new Date(appointment.appointment_date).toLocaleDateString()}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Time</p>
                                <p className="text-lg font-semibold">{appointment.appointment_time}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Status</p>
                                <Badge variant={appointment.status === 'scheduled' ? 'default' : 'secondary'}>
                                    {appointment.status}
                                </Badge>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Duration</p>
                                <p className="text-lg font-semibold">{appointment.duration_minutes} minutes</p>
                            </div>
                            {appointment.user && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Clinician</p>
                                    <p className="text-lg font-semibold">Dr. {appointment.user.name}</p>
                                </div>
                            )}
                            {appointment.exam_room && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Room</p>
                                    <p className="text-lg font-semibold">{appointment.exam_room.name}</p>
                                </div>
                            )}
                        </div>

                        {appointment.notes && (
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Notes</p>
                                <p className="mt-1">{appointment.notes}</p>
                            </div>
                        )}

                        {canCancel && appointment.status === 'scheduled' && (
                            <div className="pt-4 border-t">
                                <Button
                                    variant="destructive"
                                    onClick={handleCancel}
                                    disabled={isCancelling}
                                >
                                    {isCancelling ? 'Cancelling...' : 'Cancel Appointment'}
                                </Button>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    You can cancel appointments up to 24 hours before the scheduled time.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </PatientLayout>
    );
}

