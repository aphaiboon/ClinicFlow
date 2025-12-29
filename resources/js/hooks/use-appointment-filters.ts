import { router } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import { index } from '@/routes/appointments/index';

interface AppointmentFilters {
    status?: string;
    date?: string;
    clinician_id?: string;
    exam_room_id?: string;
}

interface UseAppointmentFiltersProps {
    initialFilters?: AppointmentFilters;
}

export function useAppointmentFilters({ initialFilters }: UseAppointmentFiltersProps = {}) {
    const [statusFilter, setStatusFilter] = useState(
        initialFilters?.status && initialFilters.status !== '' ? initialFilters.status : 'all',
    );
    const [dateFilter, setDateFilter] = useState(initialFilters?.date || '');
    const [clinicianFilter, setClinicianFilter] = useState(
        initialFilters?.clinician_id && initialFilters.clinician_id !== ''
            ? initialFilters.clinician_id
            : 'all',
    );
    const [examRoomFilter, setExamRoomFilter] = useState(
        initialFilters?.exam_room_id && initialFilters.exam_room_id !== ''
            ? initialFilters.exam_room_id
            : 'all',
    );

    const applyFilters = useCallback(() => {
        const params: Record<string, string> = {};
        if (statusFilter && statusFilter !== 'all') {
            params.status = statusFilter;
        }
        if (dateFilter) {
            params.date = dateFilter;
        }
        if (clinicianFilter && clinicianFilter !== 'all') {
            params.clinician_id = clinicianFilter;
        }
        if (examRoomFilter && examRoomFilter !== 'all') {
            params.exam_room_id = examRoomFilter;
        }

        router.get(index().url, params, { preserveState: true });
    }, [statusFilter, dateFilter, clinicianFilter, examRoomFilter]);

    const updateQuickFilters = useCallback(
        (quickFilters: { date?: string; status?: string }) => {
            const params: Record<string, string> = {};
            
            // Build params from current filters and new quick filters
            if (quickFilters.date) {
                params.date = quickFilters.date;
            } else if (dateFilter) {
                params.date = dateFilter;
            }
            
            if (quickFilters.status) {
                params.status = quickFilters.status;
            } else if (statusFilter && statusFilter !== 'all') {
                params.status = statusFilter;
            }
            
            if (clinicianFilter && clinicianFilter !== 'all') {
                params.clinician_id = clinicianFilter;
            }
            
            if (examRoomFilter && examRoomFilter !== 'all') {
                params.exam_room_id = examRoomFilter;
            }
            
            // Use router.get with replace to update URL without adding to history
            // Not using preserveState to avoid hydration mismatches during navigation
            router.get(index().url, params, {
                preserveScroll: true,
                replace: true,
            });
        },
        [dateFilter, statusFilter, clinicianFilter, examRoomFilter],
    );

    return {
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
        filters: {
            status: statusFilter !== 'all' ? statusFilter : undefined,
            date: dateFilter || undefined,
            clinician_id: clinicianFilter !== 'all' ? clinicianFilter : undefined,
            exam_room_id: examRoomFilter !== 'all' ? examRoomFilter : undefined,
        },
    };
}

