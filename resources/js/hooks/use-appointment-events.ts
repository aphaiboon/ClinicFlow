import { useCallback } from 'react';
import { router } from '@inertiajs/react';
import { reschedule } from '@/routes/appointments/index';
import type { CalendarEvent } from '@/types';
import type { EventDropArg } from '@fullcalendar/core';

interface UseAppointmentEventsProps {
    onConflictDetected: (data: {
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
    }) => void;
}

export function useAppointmentEvents({ onConflictDetected }: UseAppointmentEventsProps) {
    const handleEventDrop = useCallback(
        async (dropInfo: EventDropArg) => {
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
                const response = await fetch(reschedule(extendedProps.appointmentId).url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        appointment_date: newDate,
                        appointment_time: newTime,
                        duration_minutes: durationMinutes,
                    }),
                });

                const data = await response.json();

                if (!response.ok && data.conflicts) {
                    // Show conflict modal
                    onConflictDetected({
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
        },
        [onConflictDetected],
    );

    const handleConfirmReschedule = useCallback(
        async (dropInfo: EventDropArg | null) => {
            if (!dropInfo) {
                return;
            }

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
                const response = await fetch(reschedule(extendedProps.appointmentId).url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        appointment_date: newDate,
                        appointment_time: newTime,
                        duration_minutes: durationMinutes,
                        force_reschedule: true,
                    }),
                });

                if (response.ok) {
                    router.reload({ only: ['appointments'] });
                } else {
                    const data = await response.json();
                    alert(data.message || 'Failed to reschedule appointment');
                }
            } catch (error) {
                console.error('Error rescheduling appointment:', error);
                alert('Failed to reschedule appointment. Please try again.');
            }
        },
        [],
    );

    const handleDateClick = useCallback((date: Date, onOpenModal: (date: string, time: string) => void) => {
        // Open quick create modal with pre-filled date and time
        const dateStr = date.toISOString().split('T')[0];
        const timeStr = date.toTimeString().split(' ')[0].slice(0, 5);
        onOpenModal(dateStr, timeStr);
    }, []);

    return {
        handleEventDrop,
        handleConfirmReschedule,
        handleDateClick,
    };
}

