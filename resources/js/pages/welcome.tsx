import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Calendar, Building, Users, Activity } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    if (auth.user) {
        return null;
    }

    const features = [
        {
            icon: Users,
            title: 'Patient Management',
            description: 'Register and manage patient records with comprehensive demographic information',
        },
        {
            icon: Calendar,
            title: 'Appointment Scheduling',
            description: 'Schedule, reschedule, and manage patient appointments efficiently',
        },
        {
            icon: Building,
            title: 'Exam Room Management',
            description: 'Assign patients to exam rooms and track room availability',
        },
        {
            icon: Activity,
            title: 'Audit & Compliance',
            description: 'Complete audit trails and compliance-ready logging for healthcare operations',
        },
    ];

    return (
        <>
            <Head title="ClinicFlow - Clinic Management System" />
            <div className="flex min-h-screen flex-col bg-background">
                <header className="border-b border-border">
                    <div className="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-2">
                            <div className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-5 fill-current" />
                            </div>
                            <span className="text-lg font-semibold">ClinicFlow</span>
                        </div>
                        <nav className="flex items-center gap-4">
                            <Button asChild variant="ghost">
                                <Link href={login()}>Sign In</Link>
                            </Button>
                            {canRegister && (
                                <Button asChild>
                                    <Link href={register()}>Register</Link>
                                </Button>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex flex-1 flex-col">
                    <section className="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                        <div className="w-full max-w-6xl">
                            <div className="text-center">
                                <div className="mb-6 flex justify-center">
                                    <div className="flex size-16 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                                        <AppLogoIcon className="size-10 fill-current" />
                                    </div>
                                </div>
                                <h1 className="mb-4 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                                    ClinicFlow
                                </h1>
                                <p className="mb-8 text-xl text-muted-foreground sm:text-2xl">
                                    Clinic Management System for Healthcare Staff
                                </p>
                                <p className="mx-auto mb-12 max-w-2xl text-base text-muted-foreground sm:text-lg">
                                    Streamline your clinic operations with comprehensive patient management,
                                    appointment scheduling, and exam room coordination—all designed for healthcare
                                    professionals.
                                </p>
                                <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                    <Button asChild size="lg">
                                        <Link href={login()}>Sign In</Link>
                                    </Button>
                                    {canRegister && (
                                        <Button asChild size="lg" variant="outline">
                                            <Link href={register()}>Create Account</Link>
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <div className="mt-24 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                                {features.map((feature) => {
                                    const Icon = feature.icon;
                                    return (
                                        <div
                                            key={feature.title}
                                            className="rounded-lg border border-border bg-card p-6 text-center shadow-sm transition-shadow hover:shadow-md"
                                        >
                                            <div className="mb-4 flex justify-center">
                                                <div className="flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                                    <Icon className="size-6" />
                                                </div>
                                            </div>
                                            <h3 className="mb-2 text-lg font-semibold">{feature.title}</h3>
                                            <p className="text-sm text-muted-foreground">
                                                {feature.description}
                                            </p>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border py-6">
                    <div className="container mx-auto px-4 text-center text-sm text-muted-foreground sm:px-6 lg:px-8">
                        <p>
                            ClinicFlow - Healthcare clinic management system. Designed for demonstration and
                            educational purposes.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
