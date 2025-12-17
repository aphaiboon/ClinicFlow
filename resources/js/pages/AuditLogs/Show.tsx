import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type AuditLog } from '@/types';
import { Head } from '@inertiajs/react';
import { index, show } from '@/routes/audit-logs';

interface AuditLogsShowProps {
    auditLog: AuditLog;
}

const breadcrumbs = (auditLog: AuditLog): BreadcrumbItem[] => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Audit Logs',
        href: index().url,
    },
    {
        title: `Log #${auditLog.id}`,
        href: show(auditLog.id).url,
    },
];

export default function Show({ auditLog }: AuditLogsShowProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs(auditLog)}>
            <Head title={`Audit Log #${auditLog.id}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Audit Log #{auditLog.id}</h1>
                    <div className="mt-2">
                        <Badge>{auditLog.action}</Badge>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Activity Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Action</div>
                                <div className="capitalize">{auditLog.action}</div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Resource Type</div>
                                <div>{auditLog.resource_type}</div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Resource ID</div>
                                <div>{auditLog.resource_id}</div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Timestamp</div>
                                <div>{new Date(auditLog.created_at).toLocaleString()}</div>
                            </div>
                            {auditLog.user && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">User</div>
                                    <div>{auditLog.user.name} ({auditLog.user.email})</div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Request Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {auditLog.ip_address && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">IP Address</div>
                                    <div>{auditLog.ip_address}</div>
                                </div>
                            )}
                            {auditLog.user_agent && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">User Agent</div>
                                    <div className="text-sm break-all">{auditLog.user_agent}</div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {auditLog.changes && Object.keys(auditLog.changes).length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Changes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <pre className="rounded-md bg-muted p-4 text-sm overflow-auto">
                                {JSON.stringify(auditLog.changes, null, 2)}
                            </pre>
                        </CardContent>
                    </Card>
                )}

                {auditLog.metadata && Object.keys(auditLog.metadata).length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Metadata</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <pre className="rounded-md bg-muted p-4 text-sm overflow-auto">
                                {JSON.stringify(auditLog.metadata, null, 2)}
                            </pre>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
