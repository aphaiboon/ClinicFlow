import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/exam-rooms';
import { type BreadcrumbItem, type ExamRoom } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { MapPin, Plus } from 'lucide-react';

interface ExamRoomsIndexProps {
    rooms: {
        data: ExamRoom[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Exam Rooms',
        href: index().url,
    },
];

export default function Index({ rooms }: ExamRoomsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Exam Rooms" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Exam Rooms
                        </h1>
                        <p className="text-muted-foreground">
                            Manage exam rooms and availability
                        </p>
                    </div>
                    <Link href={store().url}>
                        <Button>
                            <Plus className="mr-2 size-4" />
                            Add Room
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All Rooms</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {rooms.data.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">
                                No exam rooms found.
                            </div>
                        ) : (
                            <>
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {rooms.data.map((room) => (
                                        <Card key={room.id}>
                                            <CardHeader>
                                                <div className="flex items-center justify-between">
                                                    <CardTitle className="text-lg">
                                                        <Link
                                                            href={`/exam-rooms/${room.id}`}
                                                            className="hover:underline"
                                                        >
                                                            {room.name}
                                                        </Link>
                                                    </CardTitle>
                                                    <Badge
                                                        variant={
                                                            room.is_active
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {room.is_active
                                                            ? 'Active'
                                                            : 'Inactive'}
                                                    </Badge>
                                                </div>
                                                <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                    <MapPin className="size-4" />
                                                    {room.room_number}
                                                    {room.floor &&
                                                        ` • Floor ${room.floor}`}
                                                </div>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="space-y-2 text-sm">
                                                    <div>
                                                        <span className="font-medium">
                                                            Capacity:
                                                        </span>{' '}
                                                        {room.capacity}
                                                    </div>
                                                    {room.equipment &&
                                                        room.equipment.length >
                                                            0 && (
                                                            <div>
                                                                <span className="font-medium">
                                                                    Equipment:
                                                                </span>{' '}
                                                                {room.equipment.join(
                                                                    ', ',
                                                                )}
                                                            </div>
                                                        )}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>

                                {rooms.last_page > 1 && (
                                    <div className="mt-6 flex items-center justify-center gap-2">
                                        {rooms.links.map((link, index) => {
                                            if (link.url === null) {
                                                return (
                                                    <span
                                                        key={index}
                                                        className="px-3 py-2 text-sm text-muted-foreground"
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                );
                                            }

                                            return (
                                                <Link
                                                    key={index}
                                                    href={link.url}
                                                    className={`rounded-md px-3 py-2 text-sm ${
                                                        link.active
                                                            ? 'bg-primary text-primary-foreground'
                                                            : 'hover:bg-accent'
                                                    }`}
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                />
                                            );
                                        })}
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
