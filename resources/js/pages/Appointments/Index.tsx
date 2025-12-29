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
import { index, reschedule, store } from '@/routes/appointments/index';
import {
    type Appointment,
    type BreadcrumbItem,
    type CalendarEvent,
    type CalendarViewType,
    type ExamRoom,
    type Patient,
    type User,
} from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Clock, MapPin, Plus, User as UserIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import CalendarViewToggle from '@/components/appointments/CalendarViewToggle';
import AppointmentCalendar from '@/components/appointments/AppointmentCalendar';
import AppointmentDetailModal from '@/components/appointments/AppointmentDetailModal';
import ConflictWarningModal from '@/components/appointments/ConflictWarningModal';
import QuickCreateAppointmentModal from '@/components/appointments/QuickCreateAppointmentModal';
import QuickFilters from '@/components/appointments/QuickFilters';
import type { EventDropArg } from '@fullcalendar/core';

type AppointmentStatus = 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'no_show';

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
        exam_room_id?: string;
    };
    clinicians: User[];
    patients?: Patient[];
    examRooms?: ExamRoom[];
    operatingHours?: {
        startTime: string;
        endTime: string;
    };
    timeSlotInterval?: number;
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
    patients = [],
    examRooms = [],
    operatingHours = { startTime: '08:00:00', endTime: '18:00:00' },
    timeSlotInterval = 15,
}: AppointmentsIndexProps) {
    const [currentView, setCurrentView] = useState<CalendarViewType>('week');
    const [statusFilter, setStatusFilter] = useState(
        filters?.status && filters.status !== '' ? filters.status : 'all',
    );
    const [dateFilter, setDateFilter] = useState(filters?.date || '');
    const [clinicianFilter, setClinicianFilter] = useState(
        filters?.clinician_id && filters.clinician_id !== '' ? filters.clinician_id : 'all',
    );
    const [examRoomFilter, setExamRoomFilter] = useState(
        filters?.exam_room_id && filters.exam_room_id !== '' ? filters.exam_room_id : 'all',
    );
    const [selectedAppointment, setSelectedAppointment] =
        useState<CalendarEvent['extendedProps'] | null>(null);
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [showConflictModal, setShowConflictModal] = useState(false);
    const [showQuickCreateModal, setShowQuickCreateModal] = useState(false);
    const [quickCreateDate, setQuickCreateDate] = useState<string>('');
    const [quickCreateTime, setQuickCreateTime] = useState<string>('');
    const [conflictData, setConflictData] = useState<{
        conflicts: Array<{
            type: 'clinician' | 'room' | 'both';
            message: string;
            conflictingAppointments: Array<{
                id: number;
                patientName: string;
                time: string;
            }>;
        }>;
        newAppointmentDetails: {
            patientName: string;
            newTime: string;
            newDate: string;
            newRoom?: string;
            newClinician?: string;
        };
        dropInfo: EventDropArg | null;
    } | null>(null);

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (statusFilter && statusFilter !== 'all') params.status = statusFilter;
        if (dateFilter) params.date = dateFilter;
        if (clinicianFilter && clinicianFilter !== 'all')
            params.clinician_id = clinicianFilter;
        if (examRoomFilter && examRoomFilter !== 'all')
            params.exam_room_id = examRoomFilter;

        router.get(index().url, params, { preserveState: true });
    };

    const handleEventClick = useCallback(
        (appointment: CalendarEvent['extendedProps']) => {
            setSelectedAppointment(appointment);
            setShowDetailModal(true);
        },
        [],
    );

    const handleEventDrop = useCallback(async (dropInfo: EventDropArg) => {
        const event = dropInfo.event;
        const extendedProps = event.extendedProps as CalendarEvent['extendedProps'];
        const newStart = dropInfo.event.start!;
        const newEnd = dropInfo.event.end || newStart;

        const newDate = newStart.toISOString().split('T')[0];
        const newTime = newStart.toTimeString().split(' ')[0].slice(0, 5); // HH:MM format
        const durationMinutes =
            Math.round((newEnd.getTime() - newStart.getTime()) / (1000 * 60)) ||
            extendedProps.durationMinutes;

        try {
            const response = await fetch(
                reschedule(extendedProps.appointmentId).url,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        appointment_date: newDate,
                        appointment_time: newTime,
                        duration_minutes: durationMinutes,
                    }),
                },
            );

            const data = await response.json();

            if (!response.ok && data.conflicts) {
                // Show conflict modal
                setConflictData({
                    conflicts: data.conflicts,
                    newAppointmentDetails: {
                        patientName: extendedProps.patientName,
                        newTime: `${newTime} (${durationMinutes} min)`,
                        newDate: newDate,
                        newRoom: extendedProps.examRoomName,
                        newClinician: extendedProps.clinicianName,
                    },
                    dropInfo,
                });
                setShowConflictModal(true);
            } else if (response.ok) {
                // Success - reload the page to refresh calendar
                router.reload({ only: ['appointments'] });
            } else {
                // Other error
                alert(data.message || 'Failed to reschedule appointment');
            }
        } catch (error) {
            console.error('Error rescheduling appointment:', error);
            alert('Failed to reschedule appointment. Please try again.');
        }
    }, []);

    const handleConfirmReschedule = useCallback(async () => {
        if (!conflictData?.dropInfo) {
            return;
        }

        const dropInfo = conflictData.dropInfo;
        const event = dropInfo.event;
        const extendedProps = event.extendedProps as CalendarEvent['extendedProps'];
        const newStart = dropInfo.event.start!;
        const newEnd = dropInfo.event.end || newStart;

        const newDate = newStart.toISOString().split('T')[0];
        const newTime = newStart.toTimeString().split(' ')[0].slice(0, 5);
        const durationMinutes =
            Math.round((newEnd.getTime() - newStart.getTime()) / (1000 * 60)) ||
            extendedProps.durationMinutes;

        try {
            const response = await fetch(
                reschedule(extendedProps.appointmentId).url,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        appointment_date: newDate,
                        appointment_time: newTime,
                        duration_minutes: durationMinutes,
                        force_reschedule: true,
                    }),
                },
            );

            if (response.ok) {
                setShowConflictModal(false);
                setConflictData(null);
                router.reload({ only: ['appointments'] });
            } else {
                const data = await response.json();
                alert(data.message || 'Failed to reschedule appointment');
            }
        } catch (error) {
            console.error('Error rescheduling appointment:', error);
            alert('Failed to reschedule appointment. Please try again.');
        }
    }, [conflictData]);

    const handleQuickFilterChange = useCallback(
        (quickFilters: { date?: string; status?: string }) => {
            const params: Record<string, string> = { ...filters };
            if (quickFilters.date) {
                params.date = quickFilters.date;
                setDateFilter(quickFilters.date);
            }
            if (quickFilters.status) {
                params.status = quickFilters.status;
                setStatusFilter(quickFilters.status);
            }
            router.get(index().url, params, { preserveState: true });
        },
        [filters],
    );

    const handleDateClick = useCallback(
        (date: Date) => {
            // Open quick create modal with pre-filled date and time
            const dateStr = date.toISOString().split('T')[0];
            const timeStr = date.toTimeString().split(' ')[0].slice(0, 5);
            setQuickCreateDate(dateStr);
            setQuickCreateTime(timeStr);
            setShowQuickCreateModal(true);
        },
        [],
    );

    // Parse operating hours to remove seconds if present
    const operatingHoursFormatted = {
        startTime: operatingHours.startTime.split(':').slice(0, 2).join(':'),
        endTime: operatingHours.endTime.split(':').slice(0, 2).join(':'),
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appointments" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Appointments
                        </h1>
                        <p className="text-muted-foreground">
                            Manage appointments and schedules
                        </p>
                    </div>
                    <Link href="/appointments/create">
                        <Button>
                            <Plus className="mr-2 size-4" />
                            Schedule Appointment
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle>View Options</CardTitle>
                            <CalendarViewToggle
                                currentView={currentView}
                                onViewChange={setCurrentView}
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-4">
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">Status</label>
                                <Select
                                    value={statusFilter || 'all'}
                                    onValueChange={(value) => setStatusFilter(value || 'all')}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Statuses</SelectItem>
                                        <SelectItem value="scheduled">Scheduled</SelectItem>
                                        <SelectItem value="in_progress">
                                            In Progress
                                        </SelectItem>
                                        <SelectItem value="completed">Completed</SelectItem>
                                        <SelectItem value="cancelled">Cancelled</SelectItem>
                                        <SelectItem value="no_show">No Show</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <label className="text-sm font-medium">Date</label>
                                <Input
                                    type="date"
                                    value={dateFilter}
                                    onChange={(e) => setDateFilter(e.target.value)}
                                />
                            </div>

                            <div className="grid gap-2">
                                <label className="text-sm font-medium">Clinician</label>
                                <Select
                                    value={clinicianFilter || 'all'}
                                    onValueChange={(value) => setClinicianFilter(value || 'all')}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All clinicians" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Clinicians</SelectItem>
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

                            {examRooms.length > 0 && (
                                <div className="grid gap-2">
                                    <label className="text-sm font-medium">
                                        Exam Room
                                    </label>
                                    <Select
                                        value={examRoomFilter || 'all'}
                                        onValueChange={(value) => setExamRoomFilter(value || 'all')}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All rooms" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Rooms</SelectItem>
                                            {examRooms.map((room) => (
                                                <SelectItem
                                                    key={room.id}
                                                    value={room.id.toString()}
                                                >
                                                    {room.name} ({room.room_number})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="flex items-end">
                                <Button onClick={applyFilters} className="w-full">
                                    Apply Filters
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {currentView === 'list' ? (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>All Appointments</CardTitle>
                                <QuickFilters onFilterChange={handleQuickFilterChange} />
                            </div>
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
                                                        {appointment.patient?.first_name}{' '}
                                                        {appointment.patient?.last_name}
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
                                                                {appointment.appointment_time}
                                                            </span>
                                                        </div>
                                                        {appointment.user && (
                                                            <div className="flex items-center gap-2">
                                                                <UserIcon className="size-4" />
                                                                <span>{appointment.user.name}</span>
                                                            </div>
                                                        )}
                                                        {appointment.examRoom && (
                                                            <div className="flex items-center gap-2">
                                                                <MapPin className="size-4" />
                                                                <span>
                                                                    {appointment.examRoom.name} (
                                                                    {appointment.examRoom.room_number}
                                                                    )
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
                                                        <Button variant="outline" size="sm">
                                                            Edit
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {appointments.last_page > 1 && (
                                        <div className="mt-6 flex items-center justify-center gap-2">
                                            {appointments.links.map((link, index) => {
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
                                                        className={`rounded-md px-3 py-2 text-sm ${link.active
                                                            ? 'bg-primary text-primary-foreground'
                                                            : 'hover:bg-accent'
                                                            }`}
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                );
                                            })}
                                        </div>
                                    )}
                                </>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {currentView === 'day'
                                    ? 'Day View'
                                    : currentView === 'week'
                                        ? 'Week View'
                                        : 'Month View'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <AppointmentCalendar
                                initialView={currentView}
                                filters={{
                                    status: statusFilter !== 'all' ? statusFilter : undefined,
                                    clinician_id:
                                        clinicianFilter !== 'all'
                                            ? clinicianFilter
                                            : undefined,
                                    exam_room_id:
                                        examRoomFilter !== 'all'
                                            ? examRoomFilter
                                            : undefined,
                                    date: dateFilter || undefined,
                                }}
                                operatingHours={operatingHoursFormatted}
                                timeSlotInterval={timeSlotInterval}
                                onEventClick={handleEventClick}
                                onEventDrop={handleEventDrop}
                                onDateClick={handleDateClick}
                            />
                        </CardContent>
                    </Card>
                )}
            </div>

            <AppointmentDetailModal
                isOpen={showDetailModal}
                onClose={() => {
                    setShowDetailModal(false);
                    setSelectedAppointment(null);
                }}
                appointment={selectedAppointment}
            />

            <ConflictWarningModal
                isOpen={showConflictModal}
                onClose={() => {
                    setShowConflictModal(false);
                    setConflictData(null);
                }}
                onConfirm={handleConfirmReschedule}
                conflicts={conflictData?.conflicts || []}
                newAppointmentDetails={
                    conflictData?.newAppointmentDetails || {
                        patientName: '',
                        newTime: '',
                        newDate: '',
                    }
                }
            />

            <QuickCreateAppointmentModal
                isOpen={showQuickCreateModal}
                onClose={() => {
                    setShowQuickCreateModal(false);
                    setQuickCreateDate('');
                    setQuickCreateTime('');
                }}
                preselectedDate={quickCreateDate}
                preselectedTime={quickCreateTime}
                patients={patients || []}
                clinicians={clinicians || []}
                examRooms={examRooms || []}
            />
        </AppLayout>
    );
}
