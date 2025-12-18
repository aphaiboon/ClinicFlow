import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type AppointmentStatus =
    | 'scheduled'
    | 'in_progress'
    | 'completed'
    | 'cancelled'
    | 'no_show';

interface StatusBadgeProps {
    status: AppointmentStatus;
    className?: string;
}

const statusConfig: Record<
    AppointmentStatus,
    {
        label: string;
        variant: 'default' | 'secondary' | 'destructive' | 'outline';
    }
> = {
    scheduled: { label: 'Scheduled', variant: 'default' },
    in_progress: { label: 'In Progress', variant: 'secondary' },
    completed: { label: 'Completed', variant: 'outline' },
    cancelled: { label: 'Cancelled', variant: 'destructive' },
    no_show: { label: 'No Show', variant: 'destructive' },
};

export function StatusBadge({ status, className }: StatusBadgeProps) {
    const config = statusConfig[status];
    return (
        <Badge variant={config.variant} className={cn(className)}>
            {config.label}
        </Badge>
    );
}
