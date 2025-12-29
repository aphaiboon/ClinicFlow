import { AppointmentForm } from '@/components/appointments/AppointmentForm';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { store } from '@/routes/appointments/index';
import {
    type ExamRoom,
    type Patient,
    type User,
} from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface QuickCreateAppointmentModalProps {
    isOpen: boolean;
    onClose: () => void;
    preselectedDate: string;
    preselectedTime: string;
    patients: Patient[];
    clinicians: User[];
    examRooms: ExamRoom[];
}

export default function QuickCreateAppointmentModal({
    isOpen,
    onClose,
    preselectedDate,
    preselectedTime,
    patients,
    clinicians,
    examRooms,
}: QuickCreateAppointmentModalProps) {
    const handleSuccess = () => {
        onClose();
        router.reload({ only: ['appointments'] });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Quick Schedule Appointment</DialogTitle>
                    <DialogDescription>
                        Schedule an appointment for{' '}
                        {preselectedDate &&
                            new Date(preselectedDate).toLocaleDateString('en-US', {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            })}{' '}
                        {preselectedTime && `at ${preselectedTime}`}
                    </DialogDescription>
                </DialogHeader>

                <div className="py-4">
                    <AppointmentForm
                        route={store()}
                        patients={patients}
                        clinicians={clinicians}
                        examRooms={examRooms}
                        preselectedDate={preselectedDate}
                        preselectedTime={preselectedTime}
                        onSuccess={handleSuccess}
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}

