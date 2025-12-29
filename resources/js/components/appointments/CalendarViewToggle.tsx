import { CalendarViewType } from '@/types';
import { cn } from '@/lib/utils';
import { Calendar, List } from 'lucide-react';
import { useEffect, useState } from 'react';
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

    useEffect(() => {
        setMounted(true);
        // Load view preference from sessionStorage
        const savedView = sessionStorage.getItem(STORAGE_KEY) as CalendarViewType | null;
        if (savedView && savedView !== currentView && ['list', 'day', 'week', 'month'].includes(savedView)) {
            onViewChange(savedView);
        }
    }, []);

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

