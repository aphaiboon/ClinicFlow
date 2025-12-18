import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';
import { Calendar, Home, LogOut, User } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PatientLayoutProps {
    title?: string;
}

export default function PatientLayout({
    children,
    title,
}: PropsWithChildren<PatientLayoutProps>) {
    const { auth } = usePage<SharedData>().props;
    const patient = auth.patient;

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <Head title={title} />

            {/* Header */}
            <header className="border-b border-border bg-card">
                <div className="container mx-auto flex items-center justify-between px-4 py-4">
                    <div className="flex items-center gap-4">
                        <h1 className="text-xl font-bold">ClinicFlow Patient Portal</h1>
                    </div>
                    <div className="flex items-center gap-4">
                        {patient && (
                            <span className="text-sm text-muted-foreground">
                                {patient.first_name} {patient.last_name}
                            </span>
                        )}
                        <form method="POST" action="/patient/logout">
                            <Button type="submit" variant="outline" size="sm">
                                <LogOut className="mr-2 size-4" />
                                Logout
                            </Button>
                        </form>
                    </div>
                </div>
            </header>

            {/* Navigation */}
            <nav className="border-b border-border bg-card/50">
                <div className="container mx-auto px-4">
                    <div className="flex gap-1">
                        <Link
                            href="/patient/dashboard"
                            className="flex items-center gap-2 px-4 py-3 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <Home className="size-4" />
                            Dashboard
                        </Link>
                        <Link
                            href="/patient/appointments"
                            className="flex items-center gap-2 px-4 py-3 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <Calendar className="size-4" />
                            Appointments
                        </Link>
                        <Link
                            href="/patient/profile"
                            className="flex items-center gap-2 px-4 py-3 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <User className="size-4" />
                            Profile
                        </Link>
                    </div>
                </div>
            </nav>

            {/* Main Content */}
            <main className="flex-1">
                <div className="container mx-auto px-4 py-8">{children}</div>
            </main>
        </div>
    );
}

