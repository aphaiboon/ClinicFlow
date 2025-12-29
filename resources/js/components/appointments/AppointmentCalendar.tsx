import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import { type CalendarEvent, type CalendarViewType } from '@/types';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import type { EventInput, DatesSetArg, EventClickArg, EventDropArg, DateSelectArg } from '@fullcalendar/core';

interface AppointmentCalendarProps {
    initialView: CalendarViewType;
    filters: {
        status?: string;
        date?: string;
        clinician_id?: string;
        exam_room_id?: string;
    };
    operatingHours: {
        startTime: string; // e.g., "08:00"
        endTime: string; // e.g., "18:00"
    };
    timeSlotInterval: number; // e.g., 15, 30, 60
    onEventClick: (appointment: CalendarEvent['extendedProps']) => void;
    onEventDrop: (info: EventDropArg) => void;
    onDateClick?: (date: Date) => void;
}

export default function AppointmentCalendar({
    initialView,
    filters,
    operatingHours,
    timeSlotInterval,
    onEventClick,
    onEventDrop,
    onDateClick,
}: AppointmentCalendarProps) {
    const [events, setEvents] = useState<EventInput[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const calendarRef = useRef<FullCalendar>(null);
    const [currentDateRange, setCurrentDateRange] = useState<{
        start: Date;
        end: Date;
    } | null>(null);

    // Map CalendarViewType to FullCalendar view names
    const fullCalendarView = useMemo(() => {
        switch (initialView) {
            case 'day':
                return 'timeGridDay';
            case 'week':
                return 'timeGridWeek';
            case 'month':
                return 'dayGridMonth';
            case 'list':
                return 'listWeek';
            default:
                return 'timeGridWeek';
        }
    }, [initialView]);

    // Fetch events from the calendar endpoint
    const fetchEvents = useCallback(
        async (start: Date, end: Date) => {
            setLoading(true);
            setError(null);

            try {
                const params = new URLSearchParams({
                    start_date: start.toISOString().split('T')[0],
                    end_date: end.toISOString().split('T')[0],
                });

                if (filters.status) {
                    params.append('status', filters.status);
                }
                if (filters.clinician_id) {
                    params.append('clinician_id', filters.clinician_id);
                }
                if (filters.exam_room_id) {
                    params.append('exam_room_id', filters.exam_room_id);
                }

                const response = await fetch(`/appointments/calendar?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Failed to fetch events: ${response.status}`);
                }

                const data = await response.json();
                setEvents(data.events || []);
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load events');
                setEvents([]);
            } finally {
                setLoading(false);
            }
        },
        [filters.status, filters.clinician_id, filters.exam_room_id],
    );

    // Handle date range changes
    const handleDatesSet = useCallback(
        (arg: DatesSetArg) => {
            setCurrentDateRange({ start: arg.start, end: arg.end });
            fetchEvents(arg.start, arg.end);
        },
        [fetchEvents],
    );

    // Handle event click
    const handleEventClick = useCallback(
        (clickInfo: EventClickArg) => {
            const event = clickInfo.event;
            const extendedProps = event.extendedProps as CalendarEvent['extendedProps'];
            onEventClick(extendedProps);
        },
        [onEventClick],
    );

    // Handle drag and drop
    const handleEventDrop = useCallback(
        async (dropInfo: EventDropArg) => {
            const event = dropInfo.event;
            const extendedProps = event.extendedProps as CalendarEvent['extendedProps'];
            const newStart = dropInfo.event.start!;
            const newEnd = dropInfo.event.end || newStart;

            // Revert the visual change immediately
            dropInfo.revert();

            // Call the parent handler which will check conflicts and reschedule
            onEventDrop(dropInfo);
        },
        [onEventDrop],
    );

    // Handle date/time selection (click-to-create or drag-to-create)
    const handleDateSelect = useCallback(
        (selectInfo: DateSelectArg) => {
            if (onDateClick) {
                // Use the start time for the appointment
                onDateClick(selectInfo.start);
                selectInfo.view.calendar.unselect();
            }
        },
        [onDateClick],
    );

    // Refetch events when filters change
    useEffect(() => {
        if (currentDateRange) {
            fetchEvents(currentDateRange.start, currentDateRange.end);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filters.status, filters.clinician_id, filters.exam_room_id]);

    // Parse operating hours
    const slotMinTime = useMemo(() => {
        const [hours, minutes] = operatingHours.startTime.split(':').map(Number);
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:00`;
    }, [operatingHours.startTime]);

    const slotMaxTime = useMemo(() => {
        const [hours, minutes] = operatingHours.endTime.split(':').map(Number);
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:00`;
    }, [operatingHours.endTime]);

    const slotDuration = useMemo(() => {
        return `00:${timeSlotInterval.toString().padStart(2, '0')}:00`;
    }, [timeSlotInterval]);

    return (
        <div className="relative w-full">
            {loading && (
                <div className="absolute inset-0 z-10 flex items-center justify-center bg-background/50">
                    <div className="text-sm text-muted-foreground">Loading events...</div>
                </div>
            )}
            {error && (
                <div className="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/20 dark:text-red-200">
                    {error}
                </div>
            )}
            <div className="w-full overflow-x-auto -mx-4 px-4">
                <div className="min-w-[600px]">
                    <FullCalendar
                        ref={calendarRef}
                        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin]}
                        initialView={fullCalendarView}
                        headerToolbar={{
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                        }}
                        events={events}
                        editable={true}
                        droppable={false}
                        selectable={!!onDateClick}
                        selectMirror={true}
                        selectMinDistance={5}
                        select={handleDateSelect}
                        eventClick={handleEventClick}
                        eventDrop={handleEventDrop}
                        datesSet={handleDatesSet}
                        slotMinTime={slotMinTime}
                        slotMaxTime={slotMaxTime}
                        slotDuration={slotDuration}
                        slotLabelInterval={slotDuration}
                        height="auto"
                        firstDay={1} // Monday
                        locale="en"
                        eventDisplay="block"
                        eventTimeFormat={{
                            hour: 'numeric',
                            minute: '2-digit',
                            meridiem: 'short',
                        }}
                        dayHeaderFormat={{
                            weekday: 'short',
                        }}
                        buttonText={{
                            today: 'Today',
                            month: 'Month',
                            week: 'Week',
                            day: 'Day',
                            list: 'List',
                        }}
                        className="rounded-lg border bg-background"
                        allDaySlot={false}
                        dayMaxEvents={true}
                        moreLinkClick="popover"
                        stickyHeaderDates={true}
                        stickyFooterScrollbar={true}
                        aspectRatio={1.8}
                        handleWindowResize={true}
                        windowResizeDelay={100}
                        contentHeight="auto"
                        progressiveEventRendering={true}
                    />
                </div>
            </div>
        </div>
    );
}

