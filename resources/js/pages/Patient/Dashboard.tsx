import PatientLayout from '@/layouts/patient-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, Clock, User } from 'lucide-react';
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

interface DashboardProps {
    upcomingAppointments?: Appointment[];
    recentAppointments?: Appointment[];
}

export default function PatientDashboard({
    upcomingAppointments = [],
    recentAppointments = [],
}: DashboardProps) {
    return (
        <PatientLayout title="Dashboard">
            <Head title="Patient Dashboard" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">Welcome to Your Patient Portal</h1>
                    <p className="mt-2 text-muted-foreground">
                        Manage your appointments and profile information
                    </p>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Upcoming Appointments */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="size-5" />
                                Upcoming Appointments
                            </CardTitle>
                            <CardDescription>
                                Your scheduled appointments
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {upcomingAppointments.length > 0 ? (
                                <div className="space-y-4">
                                    {upcomingAppointments.map((appointment) => (
                                        <div
                                            key={appointment.id}
                                            className="flex items-center justify-between rounded-lg border p-4"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {new Date(appointment.appointment_date).toLocaleDateString()}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {appointment.appointment_time}
                                                </p>
                                                {appointment.user && (
                                                    <p className="text-sm text-muted-foreground">
                                                        Dr. {appointment.user.name}
                                                    </p>
                                                )}
                                            </div>
                                            <Link
                                                href={`/patient/appointments/${appointment.id}`}
                                                className="text-sm text-primary hover:underline"
                                            >
                                                View
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No upcoming appointments
                                </p>
                            )}
                            <div className="mt-4">
                                <Link
                                    href="/patient/appointments"
                                    className="text-sm text-primary hover:underline"
                                >
                                    View all appointments →
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Appointments */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="size-5" />
                                Recent Appointments
                            </CardTitle>
                            <CardDescription>
                                Your past appointments
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentAppointments.length > 0 ? (
                                <div className="space-y-4">
                                    {recentAppointments.map((appointment) => (
                                        <div
                                            key={appointment.id}
                                            className="flex items-center justify-between rounded-lg border p-4"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {new Date(appointment.appointment_date).toLocaleDateString()}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {appointment.appointment_time}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    Status: {appointment.status}
                                                </p>
                                            </div>
                                            <Link
                                                href={`/patient/appointments/${appointment.id}`}
                                                className="text-sm text-primary hover:underline"
                                            >
                                                View
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No recent appointments
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Quick Actions */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <User className="size-5" />
                            Quick Actions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <Link
                                href="/patient/profile"
                                className="rounded-lg border p-4 hover:bg-accent"
                            >
                                <p className="font-medium">View Profile</p>
                                <p className="text-sm text-muted-foreground">
                                    Update your contact information
                                </p>
                            </Link>
                            <Link
                                href="/patient/appointments"
                                className="rounded-lg border p-4 hover:bg-accent"
                            >
                                <p className="font-medium">View Appointments</p>
                                <p className="text-sm text-muted-foreground">
                                    Manage your appointments
                                </p>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </PatientLayout>
    );
}

