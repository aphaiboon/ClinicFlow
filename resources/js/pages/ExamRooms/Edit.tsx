import { ExamRoomForm } from '@/components/exam-rooms/ExamRoomForm';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ExamRoom } from '@/types';
import { Head } from '@inertiajs/react';
import { index, update, show } from '@/routes/exam-rooms';

interface ExamRoomsEditProps {
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
        href: show(room.id).url,
    },
    {
        title: 'Edit',
        href: `/exam-rooms/${room.id}/edit`,
    },
];

export default function Edit({ room }: ExamRoomsEditProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs(room)}>
            <Head title={`Edit ${room.name}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Edit {room.name}
                    </h1>
                    <p className="text-muted-foreground">
                        Update exam room information
                    </p>
                </div>

                <div className="max-w-2xl">
                    <ExamRoomForm room={room} route={update(room.id)} />
                </div>
            </div>
        </AppLayout>
    );
}

