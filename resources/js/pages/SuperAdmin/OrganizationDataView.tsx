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
import { Building, Calendar, FileText, Users } from 'lucide-react';

interface Organization {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address_line_1: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    is_active: boolean;
    members?: Array<{
        id: number;
        name: string;
        email: string;
        pivot: { role: string };
    }>;
}

interface Props {
    organization: Organization;
    stats: {
        patientsCount: number;
        appointmentsCount: number;
        examRoomsCount: number;
        usersCount: number;
    };
}

export default function OrganizationDataView({ organization, stats }: Props) {
    return (
        <SuperAdminLayout
            title={`Organization: ${organization.name}`}
            breadcrumbs={[{ label: organization.name }]}
        >
            <Head title={`Organization: ${organization.name}`} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {organization.name}
                        </h1>
                        {organization.email && (
                            <p className="mt-2 text-muted-foreground">
                                {organization.email}
                            </p>
                        )}
                    </div>
                    <Badge
                        variant={
                            organization.is_active ? 'default' : 'secondary'
                        }
                    >
                        {organization.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Patients
                            </CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.patientsCount}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Appointments
                            </CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.appointmentsCount}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Exam Rooms
                            </CardTitle>
                            <Building className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.examRoomsCount}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Users
                            </CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.usersCount}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {organization.address_line_1 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Address</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p>
                                {organization.address_line_1}
                                {organization.city && `, ${organization.city}`}
                                {organization.state &&
                                    `, ${organization.state}`}
                                {organization.postal_code &&
                                    ` ${organization.postal_code}`}
                            </p>
                            {organization.phone && (
                                <p className="mt-2">{organization.phone}</p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {organization.members && organization.members.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Members</CardTitle>
                            <CardDescription>
                                Users belonging to this organization
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {organization.members.map((member) => (
                                    <div
                                        key={member.id}
                                        className="flex items-center justify-between"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {member.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {member.email}
                                            </p>
                                        </div>
                                        <Badge variant="outline">
                                            {member.pivot.role}
                                        </Badge>
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
