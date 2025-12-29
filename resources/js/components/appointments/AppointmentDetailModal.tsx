import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { StatusBadge } from '@/components/shared/StatusBadge';
import { type CalendarEvent } from '@/types';
import { Link } from '@inertiajs/react';
import { Calendar, Clock, MapPin, User as UserIcon } from 'lucide-react';
import { show } from '@/routes/appointments/index';

interface AppointmentDetailModalProps {
    isOpen: boolean;
    onClose: () => void;
    appointment: CalendarEvent['extendedProps'] | null;
}

export default function AppointmentDetailModal({
    isOpen,
    onClose,
    appointment,
}: AppointmentDetailModalProps) {
    if (!appointment) {
        return null;
    }

    const appointmentDate = appointment.start ? new Date(appointment.start) : null;
    const appointmentEnd = appointment.end ? new Date(appointment.end) : null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Appointment Details</DialogTitle>
                    <DialogDescription>
                        View appointment information and patient details
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold">
                                {appointment.patientName}
                            </h3>
                            <StatusBadge status={appointment.status as 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'no_show'} />
                        </div>

                        {appointmentDate && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Calendar className="size-4" />
                                <span>
                                    {appointmentDate.toLocaleDateString('en-US', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })}
                                </span>
                            </div>
                        )}

                        {appointmentDate && appointmentEnd && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Clock className="size-4" />
                                <span>
                                    {appointmentDate.toLocaleTimeString('en-US', {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                    })}{' '}
                                    -{' '}
                                    {appointmentEnd.toLocaleTimeString('en-US', {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                    })}
                                </span>
                                <span className="text-xs">
                                    ({appointment.durationMinutes} minutes)
                                </span>
                            </div>
                        )}

                        {appointment.clinicianName && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <UserIcon className="size-4" />
                                <span>Clinician: {appointment.clinicianName}</span>
                            </div>
                        )}

                        {appointment.examRoomName && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <MapPin className="size-4" />
                                <span>
                                    Room: {appointment.examRoomName}
                                    {appointment.examRoomId && ` (ID: ${appointment.examRoomId})`}
                                </span>
                            </div>
                        )}

                        {appointment.appointmentType && (
                            <div className="text-sm text-muted-foreground">
                                <span className="font-medium">Type: </span>
                                <span className="capitalize">
                                    {appointment.appointmentType.replace('_', ' ')}
                                </span>
                            </div>
                        )}

                        {appointment.notes && (
                            <div className="rounded-md border p-3">
                                <p className="text-sm font-medium">Notes:</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {appointment.notes}
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                <DialogFooter className="flex-col gap-2 sm:flex-row">
                    <Button variant="outline" onClick={onClose}>
                        Close
                    </Button>
                    <Link href={show(appointment.appointmentId).url}>
                        <Button>View Full Details</Button>
                    </Link>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

