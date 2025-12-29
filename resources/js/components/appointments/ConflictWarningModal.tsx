import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { AlertTriangle } from 'lucide-react';

interface ConflictDetail {
    type: 'clinician' | 'room' | 'both';
    message: string;
    conflictingAppointments: Array<{
        id: number;
        patientName: string;
        time: string;
    }>;
}

interface ConflictWarningModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    conflicts: ConflictDetail[];
    newAppointmentDetails: {
        patientName: string;
        newTime: string;
        newDate: string;
        newRoom?: string;
        newClinician?: string;
    };
}

export default function ConflictWarningModal({
    isOpen,
    onClose,
    onConfirm,
    conflicts,
    newAppointmentDetails,
}: ConflictWarningModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <div className="flex items-center gap-2">
                        <AlertTriangle className="size-5 text-yellow-600 dark:text-yellow-400" />
                        <DialogTitle>Conflicts Detected</DialogTitle>
                    </div>
                    <DialogDescription>
                        The requested time slot has conflicts. Please review the details below.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    <div className="rounded-md border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950/20">
                        <p className="text-sm font-medium text-yellow-900 dark:text-yellow-200">
                            New Appointment Details:
                        </p>
                        <div className="mt-2 space-y-1 text-sm text-yellow-800 dark:text-yellow-300">
                            <p>
                                <span className="font-medium">Patient:</span>{' '}
                                {newAppointmentDetails.patientName}
                            </p>
                            <p>
                                <span className="font-medium">Date:</span>{' '}
                                {newAppointmentDetails.newDate}
                            </p>
                            <p>
                                <span className="font-medium">Time:</span>{' '}
                                {newAppointmentDetails.newTime}
                            </p>
                            {newAppointmentDetails.newClinician && (
                                <p>
                                    <span className="font-medium">Clinician:</span>{' '}
                                    {newAppointmentDetails.newClinician}
                                </p>
                            )}
                            {newAppointmentDetails.newRoom && (
                                <p>
                                    <span className="font-medium">Room:</span>{' '}
                                    {newAppointmentDetails.newRoom}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-3">
                        {conflicts.map((conflict, index) => (
                            <div
                                key={index}
                                className="rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/20"
                            >
                                <p className="text-sm font-medium text-red-900 dark:text-red-200">
                                    {conflict.type === 'clinician'
                                        ? 'Clinician Conflict'
                                        : conflict.type === 'room'
                                          ? 'Exam Room Conflict'
                                          : 'Multiple Conflicts'}
                                </p>
                                <p className="mt-1 text-sm text-red-800 dark:text-red-300">
                                    {conflict.message}
                                </p>
                                {conflict.conflictingAppointments.length > 0 && (
                                    <div className="mt-3 space-y-2">
                                        <p className="text-xs font-medium text-red-900 dark:text-red-200">
                                            Conflicting Appointments:
                                        </p>
                                        <ul className="space-y-1 text-xs text-red-800 dark:text-red-300">
                                            {conflict.conflictingAppointments.map((apt) => (
                                                <li key={apt.id} className="flex items-center gap-2">
                                                    <span className="font-medium">
                                                        {apt.patientName}
                                                    </span>
                                                    <span>•</span>
                                                    <span>{apt.time}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    <div className="rounded-md border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950/20">
                        <p className="text-sm text-blue-900 dark:text-blue-200">
                            <strong>Note:</strong> Rescheduling despite conflicts may cause
                            scheduling issues. Please verify availability before confirming.
                        </p>
                    </div>
                </div>

                <DialogFooter className="flex-col gap-2 sm:flex-row">
                    <Button variant="outline" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button variant="destructive" onClick={onConfirm}>
                        Confirm Reschedule
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

