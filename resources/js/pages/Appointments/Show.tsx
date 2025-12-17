import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/shared/StatusBadge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Appointment } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { index, edit, cancel as cancelRoute, assignRoom as assignRoomRoute } from '@/routes/appointments';
import { Pencil, Calendar, Clock, User, MapPin, X } from 'lucide-react';
import { useCallback, useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { type ExamRoom } from '@/types';

interface AppointmentsShowProps {
    appointment: Appointment;
    examRooms?: ExamRoom[];
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
        href: `/appointments/${appointment.id}`,
    },
];

export default function Show({ appointment, examRooms = [] }: AppointmentsShowProps) {
    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const [showAssignRoomDialog, setShowAssignRoomDialog] = useState(false);
    const [selectedRoomId, setSelectedRoomId] = useState<string>(appointment.exam_room_id?.toString() || '');

    const handleCancel = useCallback(() => {
        setShowCancelDialog(true);
    }, []);

    const handleAssignRoom = useCallback(() => {
        setShowAssignRoomDialog(true);
    }, []);

    const isCancellable = appointment.status === 'scheduled';

    return (
        <AppLayout breadcrumbs={breadcrumbs(appointment)}>
            <Head title={`Appointment - ${appointment.patient?.first_name} ${appointment.patient?.last_name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Appointment #{appointment.id}
                        </h1>
                        <div className="mt-2 flex items-center gap-4">
                            <StatusBadge status={appointment.status as AppointmentStatus} />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href={edit(appointment.id).url}>
                            <Button variant="outline">
                                <Pencil className="mr-2 size-4" />
                                Edit
                            </Button>
                        </Link>
                        {isCancellable && (
                            <Button variant="destructive" onClick={handleCancel}>
                                <X className="mr-2 size-4" />
                                Cancel
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Appointment Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Date</div>
                                <div className="flex items-center gap-2">
                                    <Calendar className="size-4" />
                                    <span>{new Date(appointment.appointment_date).toLocaleDateString()}</span>
                                </div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Time</div>
                                <div className="flex items-center gap-2">
                                    <Clock className="size-4" />
                                    <span>{appointment.appointment_time}</span>
                                </div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Duration</div>
                                <div>{appointment.duration_minutes} minutes</div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Type</div>
                                <div className="capitalize">{appointment.appointment_type?.replace('_', ' ')}</div>
                            </div>
                            {appointment.notes && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Notes</div>
                                    <div>{appointment.notes}</div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>People</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {appointment.patient && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Patient</div>
                                    <Link
                                        href={`/patients/${appointment.patient.id}`}
                                        className="hover:underline"
                                    >
                                        {appointment.patient.first_name} {appointment.patient.last_name}
                                    </Link>
                                    <div className="text-sm text-muted-foreground">
                                        {appointment.patient.medical_record_number}
                                    </div>
                                </div>
                            )}
                            {appointment.user && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Clinician</div>
                                    <div className="flex items-center gap-2">
                                        <User className="size-4" />
                                        <span>{appointment.user.name}</span>
                                    </div>
                                </div>
                            )}
                            {appointment.examRoom ? (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Exam Room</div>
                                    <div className="flex items-center gap-2">
                                        <MapPin className="size-4" />
                                        <span>
                                            {appointment.examRoom.name} ({appointment.examRoom.room_number})
                                        </span>
                                    </div>
                                </div>
                            ) : examRooms.length > 0 && isCancellable ? (
                                <div>
                                    <Button variant="outline" size="sm" onClick={handleAssignRoom}>
                                        Assign Room
                                    </Button>
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>

                {appointment.cancelled_at && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Cancellation</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Cancelled At</div>
                                    <div>{new Date(appointment.cancelled_at).toLocaleString()}</div>
                                </div>
                                {appointment.cancellation_reason && (
                                    <div>
                                        <div className="text-sm font-medium text-muted-foreground">Reason</div>
                                        <div>{appointment.cancellation_reason}</div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Dialog open={showCancelDialog} onOpenChange={setShowCancelDialog}>
                    <DialogContent>
                        <Form {...cancelRoute(appointment.id).form()}>
                            {({ processing, errors }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>Cancel Appointment</DialogTitle>
                                        <DialogDescription>
                                            Are you sure you want to cancel this appointment? This action cannot be undone.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="grid gap-4 py-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="reason">Cancellation Reason *</Label>
                                            <Input
                                                id="reason"
                                                name="reason"
                                                required
                                                placeholder="Enter reason for cancellation"
                                            />
                                            <InputError message={errors.reason} />
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowCancelDialog(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" variant="destructive" disabled={processing}>
                                            {processing ? 'Cancelling...' : 'Confirm Cancellation'}
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>

                <Dialog open={showAssignRoomDialog} onOpenChange={setShowAssignRoomDialog}>
                    <DialogContent>
                        <Form {...assignRoomRoute(appointment.id).form()}>
                            {({ processing, errors }) => (
                                <>
                                    <input type="hidden" name="exam_room_id" value={selectedRoomId} />
                                    <DialogHeader>
                                        <DialogTitle>Assign Exam Room</DialogTitle>
                                        <DialogDescription>
                                            Select an exam room for this appointment.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="grid gap-4 py-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="exam_room_id">Exam Room *</Label>
                                            <Select value={selectedRoomId} onValueChange={setSelectedRoomId} required>
                                                <SelectTrigger id="exam_room_id">
                                                    <SelectValue placeholder="Select room" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {examRooms.map((room) => (
                                                        <SelectItem key={room.id} value={room.id.toString()}>
                                                            {room.name} ({room.room_number})
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.exam_room_id} />
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowAssignRoomDialog(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Assigning...' : 'Assign Room'}
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
