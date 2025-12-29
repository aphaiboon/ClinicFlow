import { Button } from '@/components/ui/button';
import { Calendar } from 'lucide-react';
import { useMemo } from 'react';

interface QuickFiltersProps {
    onFilterChange: (filters: { date?: string; status?: string }) => void;
    activeFilter?: 'today' | 'thisWeek' | 'upcoming';
}

export default function QuickFilters({
    onFilterChange,
    activeFilter,
}: QuickFiltersProps) {
    const today = useMemo(() => {
        const date = new Date();
        return date.toISOString().split('T')[0];
    }, []);

    const thisWeekStart = useMemo(() => {
        const date = new Date();
        const day = date.getDay();
        const diff = date.getDate() - day + (day === 0 ? -6 : 1); // Adjust to Monday
        const monday = new Date(date.setDate(diff));
        return monday.toISOString().split('T')[0];
    }, []);

    const handleToday = () => {
        onFilterChange({ date: today });
    };

    const handleThisWeek = () => {
        onFilterChange({ date: thisWeekStart });
    };

    const handleUpcoming = () => {
        onFilterChange({ status: 'scheduled' });
    };

    return (
        <div className="flex flex-wrap gap-2">
            <Button
                variant={activeFilter === 'today' ? 'default' : 'outline'}
                size="sm"
                onClick={handleToday}
            >
                <Calendar className="mr-2 size-4" />
                Today
            </Button>
            <Button
                variant={activeFilter === 'thisWeek' ? 'default' : 'outline'}
                size="sm"
                onClick={handleThisWeek}
            >
                <Calendar className="mr-2 size-4" />
                This Week
            </Button>
            <Button
                variant={activeFilter === 'upcoming' ? 'default' : 'outline'}
                size="sm"
                onClick={handleUpcoming}
            >
                <Calendar className="mr-2 size-4" />
                Upcoming
            </Button>
        </div>
    );
}

