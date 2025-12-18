import { SearchInput } from '@/components/shared/SearchInput';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/patients';
import { type BreadcrumbItem, type Patient } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';

interface PatientsIndexProps {
    patients: {
        data: Patient[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        search?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Patients',
        href: index().url,
    },
];

export default function Index({ patients, filters }: PatientsIndexProps) {
    const page = usePage();
    const currentSearch = (filters?.search ||
        (page.url.includes('search=')
            ? new URLSearchParams(page.url.split('?')[1]).get('search')
            : '')) as string;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Patients" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Patients
                        </h1>
                        <p className="text-muted-foreground">
                            Manage patient records
                        </p>
                    </div>
                    <Link href={store().url}>
                        <Button>
                            <Plus className="mr-2 size-4" />
                            Add Patient
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle>All Patients</CardTitle>
                            <div className="w-64">
                                <SearchInput
                                    placeholder="Search patients..."
                                    defaultValue={currentSearch}
                                />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {patients.data.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">
                                No patients found.
                            </div>
                        ) : (
                            <>
                                <div className="space-y-4">
                                    {patients.data.map((patient) => (
                                        <div
                                            key={patient.id}
                                            className="flex items-center justify-between border-b pb-4 last:border-0 last:pb-0"
                                        >
                                            <div>
                                                <Link
                                                    href={`/patients/${patient.id}`}
                                                    className="text-lg font-semibold hover:underline"
                                                >
                                                    {patient.first_name}{' '}
                                                    {patient.last_name}
                                                </Link>
                                                <p className="text-sm text-muted-foreground">
                                                    {
                                                        patient.medical_record_number
                                                    }
                                                </p>
                                                {patient.email && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {patient.email}
                                                    </p>
                                                )}
                                            </div>
                                            <Link
                                                href={`/patients/${patient.id}/edit`}
                                            >
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    Edit
                                                </Button>
                                            </Link>
                                        </div>
                                    ))}
                                </div>

                                {patients.last_page > 1 && (
                                    <div className="mt-6 flex items-center justify-center gap-2">
                                        {patients.links.map((link, index) => {
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
