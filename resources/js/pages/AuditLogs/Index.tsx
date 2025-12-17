import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { type AuditLog } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { index } from '@/routes/audit-logs';
import { useCallback, useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface AuditLogsIndexProps {
    auditLogs: {
        data: AuditLog[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        user_id?: string;
        resource_type?: string;
        action?: string;
        resource_id?: string;
        date_from?: string;
        date_to?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Audit Logs',
        href: index().url,
    },
];

export default function Index({ auditLogs, filters }: AuditLogsIndexProps) {
    const [resourceType, setResourceType] = useState(filters?.resource_type || '');
    const [action, setAction] = useState(filters?.action || '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from || '');
    const [dateTo, setDateTo] = useState(filters?.date_to || '');

    const applyFilters = useCallback(() => {
        const params: Record<string, string> = {};
        if (resourceType) params.resource_type = resourceType;
        if (action) params.action = action;
        if (dateFrom) params.date_from = dateFrom;
        if (dateTo) params.date_to = dateTo;

        router.get(index().url, params, { preserveState: true });
    }, [resourceType, action, dateFrom, dateTo]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Logs" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Audit Logs</h1>
                    <p className="text-muted-foreground">
                        View system activity and audit trail
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-4">
                            <div className="grid gap-2">
                                <Label>Resource Type</Label>
                                <Select value={resourceType} onValueChange={setResourceType}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All Types</SelectItem>
                                        <SelectItem value="App\\Models\\Patient">Patient</SelectItem>
                                        <SelectItem value="App\\Models\\Appointment">Appointment</SelectItem>
                                        <SelectItem value="App\\Models\\ExamRoom">Exam Room</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label>Action</Label>
                                <Select value={action} onValueChange={setAction}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All actions" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All Actions</SelectItem>
                                        <SelectItem value="create">Create</SelectItem>
                                        <SelectItem value="read">Read</SelectItem>
                                        <SelectItem value="update">Update</SelectItem>
                                        <SelectItem value="delete">Delete</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label>Date From</Label>
                                <Input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>Date To</Label>
                                <Input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="mt-4">
                            <Button onClick={applyFilters}>Apply Filters</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Audit Logs</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {auditLogs.data.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">
                                No audit logs found.
                            </div>
                        ) : (
                            <>
                                <div className="space-y-4">
                                    {auditLogs.data.map((log) => (
                                        <div
                                            key={log.id}
                                            className="flex items-center justify-between border-b pb-4 last:border-0 last:pb-0"
                                        >
                                            <div className="space-y-1">
                                                <Link
                                                    href={`/audit-logs/${log.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {log.action} - {log.resource_type} #{log.resource_id}
                                                </Link>
                                                <div className="text-sm text-muted-foreground">
                                                    {log.user?.name} • {new Date(log.created_at).toLocaleString()}
                                                </div>
                                                {log.ip_address && (
                                                    <div className="text-xs text-muted-foreground">
                                                        IP: {log.ip_address}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {auditLogs.last_page > 1 && (
                                    <div className="mt-6 flex items-center justify-center gap-2">
                                        {auditLogs.links.map((link, index) => {
                                            if (link.url === null) {
                                                return (
                                                    <span
                                                        key={index}
                                                        className="px-3 py-2 text-sm text-muted-foreground"
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                );
                                            }

                                            return (
                                                <Link
                                                    key={index}
                                                    href={link.url}
                                                    className={`px-3 py-2 text-sm rounded-md ${
                                                        link.active
                                                            ? 'bg-primary text-primary-foreground'
                                                            : 'hover:bg-accent'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
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
