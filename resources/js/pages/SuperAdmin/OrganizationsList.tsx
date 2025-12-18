import { Head, Link } from '@inertiajs/react';
import { Building } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { show as showOrganization } from '@/routes/super-admin/organizations';

interface Organization {
    id: number;
    name: string;
    email: string | null;
    is_active: boolean;
    users_count?: number;
    patients_count?: number;
    appointments_count?: number;
}

interface Props {
    organizations: {
        data: Organization[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
    };
}

export default function OrganizationsList({ organizations }: Props) {
    return (
        <SuperAdminLayout title="Organizations">
            <Head title="Organizations" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Organizations</h1>
                    <p className="text-muted-foreground mt-2">Manage all organizations in the system</p>
                </div>

                <div className="grid gap-4">
                    {organizations.data.map((org) => (
                        <Link key={org.id} href={showOrganization({ organization: org.id })}>
                            <Card className="hover:bg-accent transition-colors cursor-pointer">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <Building className="h-5 w-5 text-muted-foreground" />
                                            <div>
                                                <CardTitle>{org.name}</CardTitle>
                                                {org.email && (
                                                    <CardDescription>{org.email}</CardDescription>
                                                )}
                                            </div>
                                        </div>
                                        <Badge variant={org.is_active ? 'default' : 'secondary'}>
                                            {org.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex gap-4 text-sm text-muted-foreground">
                                        <span>{org.users_count ?? 0} users</span>
                                        <span>{org.patients_count ?? 0} patients</span>
                                        <span>{org.appointments_count ?? 0} appointments</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {organizations.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {organizations.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url ?? '#'}
                                className={`px-4 py-2 rounded ${
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-secondary text-secondary-foreground hover:bg-secondary/80'
                                }`}
                            >
                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </SuperAdminLayout>
    );
}

