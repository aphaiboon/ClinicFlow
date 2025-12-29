import { CalendarViewType } from '@/types';
import { Calendar, List } from 'lucide-react';
import { useEffect, useState, useCallback } from 'react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

interface CalendarViewToggleProps {
    currentView: CalendarViewType;
    onViewChange: (view: CalendarViewType) => void;
}

const VIEW_LABELS: Record<CalendarViewType, string> = {
    list: 'List',
    day: 'Day',
    week: 'Week',
    month: 'Month',
};

const STORAGE_KEY = 'appointments_calendar_view';

export default function CalendarViewToggle({
    currentView,
    onViewChange,
}: CalendarViewToggleProps) {
    const [mounted, setMounted] = useState(false);

    const handleViewChange = useCallback((view: CalendarViewType) => {
        onViewChange(view);
    }, [onViewChange]);

    useEffect(() => {
        // Set mounted flag - this is safe as it only runs once on mount
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setMounted(true);
        // Load view preference from sessionStorage
        const savedView = sessionStorage.getItem(STORAGE_KEY) as CalendarViewType | null;
        if (savedView && savedView !== currentView && ['list', 'day', 'week', 'month'].includes(savedView)) {
            handleViewChange(savedView);
        }
    }, [currentView, handleViewChange]);

    useEffect(() => {
        if (mounted) {
            sessionStorage.setItem(STORAGE_KEY, currentView);
        }
    }, [currentView, mounted]);

    if (!mounted) {
        return null;
    }

    return (
        <ToggleGroup
            type="single"
            value={currentView}
            onValueChange={(value) => {
                if (value && value !== currentView) {
                    onViewChange(value as CalendarViewType);
                }
            }}
            variant="outline"
            className="inline-flex flex-wrap gap-1"
        >
            <ToggleGroupItem value="list" aria-label="List view">
                <List className="size-4" />
                <span className="ml-1.5">{VIEW_LABELS.list}</span>
            </ToggleGroupItem>
            <ToggleGroupItem value="day" aria-label="Day view">
                <Calendar className="size-4" />
                <span className="ml-1.5">{VIEW_LABELS.day}</span>
            </ToggleGroupItem>
            <ToggleGroupItem value="week" aria-label="Week view">
                <Calendar className="size-4" />
                <span className="ml-1.5">{VIEW_LABELS.week}</span>
            </ToggleGroupItem>
            <ToggleGroupItem value="month" aria-label="Month view">
                <Calendar className="size-4" />
                <span className="ml-1.5">{VIEW_LABELS.month}</span>
            </ToggleGroupItem>
        </ToggleGroup>
    );
}

