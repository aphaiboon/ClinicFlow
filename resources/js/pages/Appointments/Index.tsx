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
import { Head, Link } from '@inertiajs/react';
import { Calendar, Clock, MapPin, Plus, User as UserIcon } from 'lucide-react';
import { useState } from 'react';
import CalendarViewToggle from '@/components/appointments/CalendarViewToggle';
import AppointmentCalendar from '@/components/appointments/AppointmentCalendar';
import AppointmentDetailModal from '@/components/appointments/AppointmentDetailModal';
import ConflictWarningModal from '@/components/appointments/ConflictWarningModal';
import QuickCreateAppointmentModal from '@/components/appointments/QuickCreateAppointmentModal';
import QuickFilters from '@/components/appointments/QuickFilters';
import { useAppointmentFilters } from '@/hooks/use-appointment-filters';
import { useAppointmentModals } from '@/hooks/use-appointment-modals';
import { useAppointmentEvents } from '@/hooks/use-appointment-events';

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

    const {
        statusFilter,
        setStatusFilter,
        dateFilter,
        setDateFilter,
        clinicianFilter,
        setClinicianFilter,
        examRoomFilter,
        setExamRoomFilter,
        applyFilters,
        updateQuickFilters,
        filters: calendarFilters,
    } = useAppointmentFilters({ initialFilters: filters });

    const {
        selectedAppointment,
        showDetailModal,
        showConflictModal,
        showQuickCreateModal,
        quickCreateDate,
        quickCreateTime,
        conflictData,
        setConflictData,
        openDetailModal,
        closeDetailModal,
        openConflictModal,
        closeConflictModal,
        openQuickCreateModal,
        closeQuickCreateModal,
    } = useAppointmentModals();

    const { handleEventDrop, handleConfirmReschedule, handleDateClick } = useAppointmentEvents({
        onConflictDetected: openConflictModal,
    });

    const onEventClick = (appointment: CalendarEvent['extendedProps']) => {
        openDetailModal(appointment);
    };

    const onDateClick = (date: Date) => {
        handleDateClick(date, openQuickCreateModal);
    };

    const onConfirmReschedule = async () => {
        if (!conflictData?.dropInfo) {
            return;
        }
        await handleConfirmReschedule(conflictData.dropInfo);
        closeConflictModal();
    };

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
                                <QuickFilters onFilterChange={updateQuickFilters} />
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
                                filters={calendarFilters}
                                operatingHours={operatingHoursFormatted}
                                timeSlotInterval={timeSlotInterval}
                                onEventClick={onEventClick}
                                onEventDrop={handleEventDrop}
                                onDateClick={onDateClick}
                            />
                        </CardContent>
                    </Card>
                )}
            </div>

            <AppointmentDetailModal
                isOpen={showDetailModal}
                onClose={closeDetailModal}
                appointment={selectedAppointment}
            />

            <ConflictWarningModal
                isOpen={showConflictModal}
                onClose={closeConflictModal}
                onConfirm={onConfirmReschedule}
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
                onClose={closeQuickCreateModal}
                preselectedDate={quickCreateDate}
                preselectedTime={quickCreateTime}
                patients={patients || []}
                clinicians={clinicians || []}
                examRooms={examRooms || []}
            />
        </AppLayout>
    );
}
