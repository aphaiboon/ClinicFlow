import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    Building,
    Calendar,
    MapPin,
    Users,
} from 'lucide-react';
import { type Appointment, type AuditLog } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface SuperAdminStats {
    organizationCount: number;
    userCount: number;
    activeOrganizationCount: number;
}

interface UserStats {
    patientCount: number;
    upcomingAppointmentsCount: number;
    activeExamRoomsCount: number;
}

interface DashboardProps {
    role: 'super_admin' | 'user';
    stats: SuperAdminStats | UserStats;
    recentAppointments?: Appointment[];
    recentActivity?: AuditLog[];
}

export default function Dashboard({
    role,
    stats,
    recentAppointments = [],
    recentActivity = [],
}: DashboardProps) {
    if (role === 'super_admin') {
        const superAdminStats = stats as SuperAdminStats;
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard" />
                <div className="space-y-6 p-6">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Super Admin Dashboard
                        </h1>
                        <p className="mt-2 text-muted-foreground">
                            Overview of all organizations and users
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Organizations
                                </CardTitle>
                                <Building className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {superAdminStats.organizationCount}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {superAdminStats.activeOrganizationCount} active
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Users
                                </CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {superAdminStats.userCount}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Across all organizations
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Active Organizations
                                </CardTitle>
                                <Activity className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {superAdminStats.activeOrganizationCount}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Out of {superAdminStats.organizationCount} total
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const userStats = stats as UserStats;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        Overview of your organization
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Patients
                            </CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {userStats.patientCount}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Registered patients
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Upcoming Appointments
                            </CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {userStats.upcomingAppointmentsCount}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Scheduled appointments
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Active Exam Rooms
                            </CardTitle>
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {userStats.activeExamRoomsCount}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Available rooms
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Appointments</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentAppointments.length > 0 ? (
                                <div className="space-y-4">
                                    {recentAppointments.map((appointment) => (
                                        <div
                                            key={appointment.id}
                                            className="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0"
                                        >
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium">
                                                    {appointment.patient?.first_name}{' '}
                                                    {appointment.patient?.last_name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {new Date(
                                                        appointment.appointment_date
                                                    ).toLocaleDateString()}{' '}
                                                    {appointment.appointment_time}
                                                </p>
                                                {appointment.user && (
                                                    <p className="text-xs text-muted-foreground">
                                                        Clinician:{' '}
                                                        {appointment.user.name}
                                                    </p>
                                                )}
                                            </div>
                                            <Link
                                                href={`/appointments/${appointment.id}`}
                                                className="text-sm text-primary hover:underline"
                                            >
                                                View
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No recent appointments
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Activity</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentActivity.length > 0 ? (
                                <div className="space-y-4">
                                    {recentActivity.map((activity) => (
                                        <div
                                            key={activity.id}
                                            className="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0"
                                        >
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium">
                                                    {activity.action}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {activity.resource_type}
                                                </p>
                                                {activity.user && (
                                                    <p className="text-xs text-muted-foreground">
                                                        By {activity.user.name}
                                                    </p>
                                                )}
                                            </div>
                                            <Link
                                                href={`/audit-logs/${activity.id}`}
                                                className="text-sm text-primary hover:underline"
                                            >
                                                View
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No recent activity
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
