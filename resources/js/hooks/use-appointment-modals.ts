import { useState, useCallback } from 'react';
import type { CalendarEvent } from '@/types';
import type { EventDropArg } from '@fullcalendar/core';

interface ConflictData {
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
}

export function useAppointmentModals() {
    const [selectedAppointment, setSelectedAppointment] =
        useState<CalendarEvent['extendedProps'] | null>(null);
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [showConflictModal, setShowConflictModal] = useState(false);
    const [showQuickCreateModal, setShowQuickCreateModal] = useState(false);
    const [quickCreateDate, setQuickCreateDate] = useState<string>('');
    const [quickCreateTime, setQuickCreateTime] = useState<string>('');
    const [conflictData, setConflictData] = useState<ConflictData | null>(null);

    const openDetailModal = useCallback((appointment: CalendarEvent['extendedProps']) => {
        setSelectedAppointment(appointment);
        setShowDetailModal(true);
    }, []);

    const closeDetailModal = useCallback(() => {
        setShowDetailModal(false);
        setSelectedAppointment(null);
    }, []);

    const openConflictModal = useCallback(
        (data: ConflictData) => {
            setConflictData(data);
            setShowConflictModal(true);
        },
        [],
    );

    const closeConflictModal = useCallback(() => {
        setShowConflictModal(false);
        setConflictData(null);
    }, []);

    const openQuickCreateModal = useCallback((date: string, time: string) => {
        setQuickCreateDate(date);
        setQuickCreateTime(time);
        setShowQuickCreateModal(true);
    }, []);

    const closeQuickCreateModal = useCallback(() => {
        setShowQuickCreateModal(false);
        setQuickCreateDate('');
        setQuickCreateTime('');
    }, []);

    return {
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
    };
}

