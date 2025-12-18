import { StatusBadge } from '@/components/shared/StatusBadge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/appointments';
import { type Appointment, type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Clock, Plus, User as UserIcon } from 'lucide-react';
import { useState } from 'react';

interface AppointmentsIndexProps {
    appointments: {
        data: Appointment[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        status?: string;
        date?: string;
        clinician_id?: string;
    };
    clinicians: User[];
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
];

export default function Index({
    appointments,
    filters,
    clinicians = [],
}: AppointmentsIndexProps) {
    const [statusFilter, setStatusFilter] = useState(filters?.status || 'all');
    const [dateFilter, setDateFilter] = useState(filters?.date || '');
    const [clinicianFilter, setClinicianFilter] = useState(
        filters?.clinician_id || 'all',
    );

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (statusFilter && statusFilter !== 'all') params.status = statusFilter;
        if (dateFilter) params.date = dateFilter;
        if (clinicianFilter && clinicianFilter !== 'all') params.clinician_id = clinicianFilter;

        router.get(index().url, params, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appointments" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Appointments
                        </h1>
                        <p className="text-muted-foreground">
                            Manage appointments and schedules
                        </p>
                    </div>
                    <Link href={store().url}>
                        <Button>
                            <Plus className="mr-2 size-4" />
                            Schedule Appointment
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-4">
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">
                                    Status
                                </label>
                                <Select
                                    value={statusFilter}
                                    onValueChange={setStatusFilter}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All Statuses
                                        </SelectItem>
                                        <SelectItem value="scheduled">
                                            Scheduled
                                        </SelectItem>
                                        <SelectItem value="in_progress">
                                            In Progress
                                        </SelectItem>
                                        <SelectItem value="completed">
                                            Completed
                                        </SelectItem>
                                        <SelectItem value="cancelled">
                                            Cancelled
                                        </SelectItem>
                                        <SelectItem value="no_show">
                                            No Show
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <label className="text-sm font-medium">
                                    Date
                                </label>
                                <Input
                                    type="date"
                                    value={dateFilter}
                                    onChange={(e) =>
                                        setDateFilter(e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <label className="text-sm font-medium">
                                    Clinician
                                </label>
                                <Select
                                    value={clinicianFilter}
                                    onValueChange={setClinicianFilter}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All clinicians" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All Clinicians
                                        </SelectItem>
                                        {clinicians.map((clinician) => (
                                            <SelectItem
                                                key={clinician.id}
                                                value={clinician.id.toString()}
                                            >
                                                {clinician.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end">
                                <Button
                                    onClick={applyFilters}
                                    className="w-full"
                                >
                                    Apply Filters
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>All Appointments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {appointments.data.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">
                                No appointments found.
                            </div>
                        ) : (
                            <>
                                <div className="space-y-4">
                                    {appointments.data.map((appointment) => (
                                        <div
                                            key={appointment.id}
                                            className="flex items-center justify-between border-b pb-4 last:border-0 last:pb-0"
                                        >
                                            <div className="space-y-1">
                                                <Link
                                                    href={`/appointments/${appointment.id}`}
                                                    className="text-lg font-semibold hover:underline"
                                                >
                                                    {
                                                        appointment.patient
                                                            ?.first_name
                                                    }{' '}
                                                    {
                                                        appointment.patient
                                                            ?.last_name
                                                    }
                                                </Link>
                                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="size-4" />
                                                        <span>
                                                            {new Date(
                                                                appointment.appointment_date,
                                                            ).toLocaleDateString()}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Clock className="size-4" />
                                                        <span>
                                                            {
                                                                appointment.appointment_time
                                                            }
                                                        </span>
                                                    </div>
                                                    {appointment.user && (
                                                        <div className="flex items-center gap-2">
                                                            <UserIcon className="size-4" />
                                                            <span>
                                                                {
                                                                    appointment
                                                                        .user
                                                                        .name
                                                                }
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <StatusBadge
                                                    status={
                                                        appointment.status as AppointmentStatus
                                                    }
                                                />
                                                <Link
                                                    href={`/appointments/${appointment.id}/edit`}
                                                >
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        Edit
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {appointments.last_page > 1 && (
                                    <div className="mt-6 flex items-center justify-center gap-2">
                                        {appointments.links.map(
                                            (link, index) => {
                                                if (link.url === null) {
                                                    return (
                                                        <span
                                                            key={index}
                                                            className="px-3 py-2 text-sm text-muted-foreground"
                                                            dangerouslySetInnerHTML={{
                                                                __html: link.label,
                                                            }}
                                                        />
                                                    );
                                                }

                                                return (
                                                    <Link
                                                        key={index}
                                                        href={link.url}
                                                        className={`rounded-md px-3 py-2 text-sm ${
                                                            link.active
                                                                ? 'bg-primary text-primary-foreground'
                                                                : 'hover:bg-accent'
                                                        }`}
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                );
                                            },
                                        )}
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
