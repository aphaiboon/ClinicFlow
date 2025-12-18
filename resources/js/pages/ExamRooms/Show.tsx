import { StatusBadge } from '@/components/shared/StatusBadge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/exam-rooms';
import { type BreadcrumbItem, type ExamRoom } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Clock, MapPin, Pencil, User } from 'lucide-react';
import { useCallback } from 'react';

interface ExamRoomsShowProps {
    room: ExamRoom;
}

const breadcrumbs = (room: ExamRoom): BreadcrumbItem[] => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Exam Rooms',
        href: index().url,
    },
    {
        title: room.name,
        href: `/exam-rooms/${room.id}`,
    },
];

export default function Show({ room }: ExamRoomsShowProps) {
    const handleActivate = useCallback(() => {
        router.post(
            `/exam-rooms/${room.id}/activate`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    }, [room.id]);

    const handleDeactivate = useCallback(() => {
        router.post(
            `/exam-rooms/${room.id}/deactivate`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    }, [room.id]);

    return (
        <AppLayout breadcrumbs={breadcrumbs(room)}>
            <Head title={room.name} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {room.name}
                        </h1>
                        <div className="mt-2 flex items-center gap-4">
                            <Badge
                                variant={
                                    room.is_active ? 'default' : 'secondary'
                                }
                            >
                                {room.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                            <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                <MapPin className="size-4" />
                                {room.room_number}
                                {room.floor && ` • Floor ${room.floor}`}
                            </div>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href={edit(room.id).url}>
                            <Button variant="outline">
                                <Pencil className="mr-2 size-4" />
                                Edit
                            </Button>
                        </Link>
                        {room.is_active ? (
                            <Button
                                variant="outline"
                                onClick={handleDeactivate}
                            >
                                Deactivate
                            </Button>
                        ) : (
                            <Button onClick={handleActivate}>Activate</Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Room Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Room Number
                                </div>
                                <div>{room.room_number}</div>
                            </div>
                            {room.floor && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">
                                        Floor
                                    </div>
                                    <div>Floor {room.floor}</div>
                                </div>
                            )}
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Capacity
                                </div>
                                <div>
                                    {room.capacity}{' '}
                                    {room.capacity === 1 ? 'person' : 'people'}
                                </div>
                            </div>
                            {room.equipment && room.equipment.length > 0 && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">
                                        Equipment
                                    </div>
                                    <ul className="list-inside list-disc">
                                        {room.equipment.map((item, index) => (
                                            <li key={index}>{item}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            {room.notes && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">
                                        Notes
                                    </div>
                                    <div>{room.notes}</div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {room.appointments && room.appointments.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Appointments</CardTitle>
                                <CardDescription>
                                    {room.appointments.length} appointment
                                    {room.appointments.length !== 1 ? 's' : ''}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {room.appointments.map((appointment) => (
                                        <div
                                            key={appointment.id}
                                            className="flex items-center justify-between border-b pb-4 last:border-0 last:pb-0"
                                        >
                                            <div className="space-y-1">
                                                <Link
                                                    href={`/appointments/${appointment.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {
                                                        appointment.patient
                                                            ?.first_name
                                                    }{' '}
                                                    {
                                                        appointment.patient
                                                            ?.last_name
                                                    }
                                                </Link>
                                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="size-4" />
                                                        <span>
                                                            {new Date(
                                                                appointment.appointment_date,
                                                            ).toLocaleDateString()}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Clock className="size-4" />
                                                        <span>
                                                            {
                                                                appointment.appointment_time
                                                            }
                                                        </span>
                                                    </div>
                                                    {appointment.user && (
                                                        <div className="flex items-center gap-2">
                                                            <User className="size-4" />
                                                            <span>
                                                                {
                                                                    appointment
                                                                        .user
                                                                        .name
                                                                }
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            <StatusBadge
                                                status={
                                                    appointment.status as AppointmentStatus
                                                }
                                            />
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
