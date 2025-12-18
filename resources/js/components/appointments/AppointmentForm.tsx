import AlertError from '@/components/alert-error';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    type Appointment,
    type ExamRoom,
    type Patient,
    type User,
} from '@/types';
import { type RouteDefinition } from '@/wayfinder';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

interface AppointmentFormProps {
    appointment?: Appointment;
    route: RouteDefinition<'post'> | RouteDefinition<'put'>;
    processing?: boolean;
    patients: Patient[];
    clinicians: User[];
    examRooms?: ExamRoom[];
    errors?: Record<string, string>;
}

export function AppointmentForm({
    appointment,
    route,
    processing = false,
    patients,
    clinicians,
    examRooms = [],
    errors: formErrors,
}: AppointmentFormProps) {
    const [appointmentType, setAppointmentType] = useState(
        appointment?.appointment_type || 'routine',
    );
    const [patientId, setPatientId] = useState<string>(
        appointment?.patient_id?.toString() || '',
    );
    const [userId, setUserId] = useState<string>(
        appointment?.user_id?.toString() || '',
    );
    const [examRoomId, setExamRoomId] = useState<string>(
        appointment?.exam_room_id?.toString() || '',
    );

    const appointmentDate = appointment?.appointment_date
        ? new Date(appointment.appointment_date).toISOString().split('T')[0]
        : '';
    const appointmentTime = appointment?.appointment_time || '';

    return (
        <>
            {formErrors?.error && <AlertError errors={[formErrors.error]} />}
            <Form {...route.form()} className="space-y-6">
                {({ processing: formProcessing, errors }) => (
                    <>
                        <input
                            type="hidden"
                            name="appointment_type"
                            value={appointmentType}
                        />
                        <input
                            type="hidden"
                            name="patient_id"
                            value={patientId}
                        />
                        <input type="hidden" name="user_id" value={userId} />
                        {examRoomId && (
                            <input
                                type="hidden"
                                name="exam_room_id"
                                value={examRoomId}
                            />
                        )}

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="patient_id">Patient *</Label>
                                <Select
                                    value={patientId}
                                    onValueChange={setPatientId}
                                    required
                                >
                                    <SelectTrigger id="patient_id">
                                        <SelectValue placeholder="Select patient" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {patients.map((patient) => (
                                            <SelectItem
                                                key={patient.id}
                                                value={patient.id.toString()}
                                            >
                                                {patient.first_name}{' '}
                                                {patient.last_name} (
                                                {patient.medical_record_number})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.patient_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="user_id">Clinician *</Label>
                                <Select
                                    value={userId}
                                    onValueChange={setUserId}
                                    required
                                >
                                    <SelectTrigger id="user_id">
                                        <SelectValue placeholder="Select clinician" />
                                    </SelectTrigger>
                                    <SelectContent>
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
                                <InputError message={errors.user_id} />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="appointment_date">Date *</Label>
                                <Input
                                    id="appointment_date"
                                    type="date"
                                    name="appointment_date"
                                    defaultValue={appointmentDate}
                                    required
                                    min={new Date().toISOString().split('T')[0]}
                                />
                                <InputError message={errors.appointment_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="appointment_time">Time *</Label>
                                <Input
                                    id="appointment_time"
                                    type="time"
                                    name="appointment_time"
                                    defaultValue={appointmentTime}
                                    required
                                />
                                <InputError message={errors.appointment_time} />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="duration_minutes">
                                    Duration (minutes) *
                                </Label>
                                <Input
                                    id="duration_minutes"
                                    type="number"
                                    name="duration_minutes"
                                    defaultValue={
                                        appointment?.duration_minutes || 30
                                    }
                                    required
                                    min={15}
                                    max={240}
                                    step={15}
                                />
                                <InputError message={errors.duration_minutes} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="appointment_type">Type *</Label>
                                <Select
                                    value={appointmentType}
                                    onValueChange={setAppointmentType}
                                    required
                                >
                                    <SelectTrigger id="appointment_type">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="routine">
                                            Routine
                                        </SelectItem>
                                        <SelectItem value="follow_up">
                                            Follow-up
                                        </SelectItem>
                                        <SelectItem value="consultation">
                                            Consultation
                                        </SelectItem>
                                        <SelectItem value="emergency">
                                            Emergency
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.appointment_type} />
                            </div>
                        </div>

                        {examRooms.length > 0 && (
                            <div className="grid gap-2">
                                <Label htmlFor="exam_room_id">
                                    Exam Room (Optional)
                                </Label>
                                <Select
                                    value={examRoomId}
                                    onValueChange={setExamRoomId}
                                >
                                    <SelectTrigger id="exam_room_id">
                                        <SelectValue placeholder="Select room (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">None</SelectItem>
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
                                <InputError message={errors.exam_room_id} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Notes</Label>
                            <textarea
                                id="notes"
                                name="notes"
                                defaultValue={appointment?.notes}
                                rows={4}
                                className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="flex gap-4">
                            <Button
                                type="submit"
                                disabled={formProcessing || processing}
                            >
                                {formProcessing || processing
                                    ? 'Saving...'
                                    : appointment
                                      ? 'Update Appointment'
                                      : 'Create Appointment'}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
