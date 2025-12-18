import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { Head } from '@inertiajs/react';
import { Building, Mail, User as UserIcon } from 'lucide-react';

interface Organization {
    id: number;
    name: string;
    pivot?: { role: string };
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
    user: User;
}

export default function UserDetails({ user }: Props) {
    return (
        <SuperAdminLayout
            title={`User: ${user.name}`}
            breadcrumbs={[{ label: user.name }]}
        >
            <Head title={`User: ${user.name}`} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {user.name}
                        </h1>
                        <p className="mt-2 text-muted-foreground">
                            {user.email}
                        </p>
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

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>User Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex items-center gap-2">
                                <Mail className="h-4 w-4 text-muted-foreground" />
                                <span>{user.email}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <UserIcon className="h-4 w-4 text-muted-foreground" />
                                <span>Role: {user.role}</span>
                            </div>
                        </CardContent>
                    </Card>

                    {user.current_organization && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Current Organization</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center gap-2">
                                    <Building className="h-4 w-4 text-muted-foreground" />
                                    <span>
                                        {user.current_organization.name}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {user.organizations && user.organizations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Organizations</CardTitle>
                            <CardDescription>
                                Organizations this user belongs to
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {user.organizations.map((org) => (
                                    <div
                                        key={org.id}
                                        className="flex items-center justify-between"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Building className="h-4 w-4 text-muted-foreground" />
                                            <span>{org.name}</span>
                                        </div>
                                        {org.pivot && (
                                            <Badge variant="outline">
                                                {org.pivot.role}
                                            </Badge>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </SuperAdminLayout>
    );
}
