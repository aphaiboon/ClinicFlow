import { ExamRoomForm } from '@/components/exam-rooms/ExamRoomForm';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/exam-rooms';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Exam Rooms',
        href: index().url,
    },
    {
        title: 'Create',
        href: store().url,
    },
];

export default function Create() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Exam Room" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Create Exam Room
                    </h1>
                    <p className="text-muted-foreground">
                        Add a new exam room to the system
                    </p>
                </div>

                <div className="max-w-2xl">
                    <ExamRoomForm route={store()} />
                </div>
            </div>
        </AppLayout>
    );
}
