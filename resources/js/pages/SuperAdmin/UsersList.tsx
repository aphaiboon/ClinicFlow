import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { show as showUser } from '@/routes/super-admin/users';
import { Head, Link } from '@inertiajs/react';
import { Users } from 'lucide-react';

interface Organization {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    current_organization?: Organization | null;
    organizations?: Organization[];
}

interface Props {
    users: {
        data: User[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
    };
}

export default function UsersList({ users }: Props) {
    return (
        <SuperAdminLayout title="Users">
            <Head title="Users" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Users</h1>
                    <p className="mt-2 text-muted-foreground">
                        Manage all users across organizations
                    </p>
                </div>

                <div className="grid gap-4">
                    {users.data.map((user) => (
                        <Link key={user.id} href={showUser({ user: user.id })}>
                            <Card className="cursor-pointer transition-colors hover:bg-accent">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <Users className="h-5 w-5 text-muted-foreground" />
                                            <div>
                                                <CardTitle>
                                                    {user.name}
                                                </CardTitle>
                                                <CardDescription>
                                                    {user.email}
                                                </CardDescription>
                                            </div>
                                        </div>
                                        <Badge
                                            variant={
                                                user.role === 'super_admin'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {user.role}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-sm text-muted-foreground">
                                        {user.current_organization ? (
                                            <span>
                                                Current:{' '}
                                                {user.current_organization.name}
                                            </span>
                                        ) : (
                                            <span>No current organization</span>
                                        )}
                                        {user.organizations &&
                                            user.organizations.length > 0 && (
                                                <span className="ml-4">
                                                    {user.organizations.length}{' '}
                                                    organization
                                                    {user.organizations
                                                        .length !== 1
                                                        ? 's'
                                                        : ''}
                                                </span>
                                            )}
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {users.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {users.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url ?? '#'}
                                className={`rounded px-4 py-2 ${
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-secondary text-secondary-foreground hover:bg-secondary/80'
                                }`}
                            >
                                <span
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </SuperAdminLayout>
    );
}
