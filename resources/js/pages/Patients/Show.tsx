import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { StatusBadge } from '@/components/shared/StatusBadge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Patient } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { index, edit, destroy } from '@/routes/patients';
import { Pencil, Trash2, Calendar, Clock, User } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useCallback } from 'react';

interface PatientsShowProps {
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
        href: `/patients/${patient.id}`,
    },
];

export default function Show({ patient }: PatientsShowProps) {
    const handleDelete = useCallback(() => {
        if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
            router.delete(destroy(patient.id).url);
        }
    }, [patient.id]);

    return (
        <AppLayout breadcrumbs={breadcrumbs(patient)}>
            <Head title={`${patient.first_name} ${patient.last_name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {patient.first_name} {patient.last_name}
                        </h1>
                        <p className="text-muted-foreground">
                            {patient.medical_record_number}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={edit(patient.id).url}>
                            <Button variant="outline">
                                <Pencil className="mr-2 size-4" />
                                Edit
                            </Button>
                        </Link>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 size-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Personal Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Date of Birth</div>
                                <div>{new Date(patient.date_of_birth).toLocaleDateString()}</div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Gender</div>
                                <div className="capitalize">{patient.gender}</div>
                            </div>
                            {patient.email && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Email</div>
                                    <div>{patient.email}</div>
                                </div>
                            )}
                            {patient.phone && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Phone</div>
                                    <div>{patient.phone}</div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Address</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {patient.address_line_1 && (
                                <div>
                                    <div>{patient.address_line_1}</div>
                                    {patient.address_line_2 && <div>{patient.address_line_2}</div>}
                                    <div>
                                        {patient.city && patient.city}
                                        {patient.city && patient.state && ', '}
                                        {patient.state && patient.state}
                                        {patient.postal_code && ` ${patient.postal_code}`}
                                    </div>
                                    {patient.country && <div>{patient.country}</div>}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {patient.appointments && patient.appointments.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Appointments</CardTitle>
                            <CardDescription>
                                {patient.appointments.length} appointment{patient.appointments.length !== 1 ? 's' : ''}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {patient.appointments.map((appointment) => (
                                    <div
                                        key={appointment.id}
                                        className="flex items-center justify-between border-b pb-4 last:border-0 last:pb-0"
                                    >
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="size-4 text-muted-foreground" />
                                                <span className="font-medium">
                                                    {new Date(appointment.appointment_date).toLocaleDateString()}
                                                </span>
                                                <Clock className="ml-2 size-4 text-muted-foreground" />
                                                <span>{appointment.appointment_time}</span>
                                            </div>
                                            {appointment.user && (
                                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <User className="size-4" />
                                                    <span>{appointment.user.name}</span>
                                                </div>
                                            )}
                                        </div>
                                        <StatusBadge status={appointment.status as any} />
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
