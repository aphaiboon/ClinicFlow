import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { login } from '@/routes';
import { register } from '@/routes/organization';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, Building, Calendar, Users } from 'lucide-react';

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
            description:
                'Register and manage patient records with comprehensive demographic information',
            color: 'text-[var(--clinicflow-teal)]',
        },
        {
            icon: Calendar,
            title: 'Appointment Scheduling',
            description:
                'Schedule, reschedule, and manage patient appointments efficiently',
            color: 'text-[var(--clinicflow-orange)]',
        },
        {
            icon: Building,
            title: 'Exam Room Management',
            description:
                'Assign patients to exam rooms and track room availability',
            color: 'text-[var(--clinicflow-brown)]',
        },
        {
            icon: Activity,
            title: 'Audit & Compliance',
            description:
                'Complete audit trails and compliance-ready logging for healthcare operations',
            color: 'text-[var(--clinicflow-light-orange)]',
        },
    ];

    return (
        <>
            <Head title="ClinicFlow - Clinic Management System" />
            <div className="flex min-h-screen flex-col bg-background">
                <header className="border-b border-border bg-card/50 backdrop-blur-sm">
                    <div className="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <img
                                src="/images/clinicflow-icon-logo.png"
                                alt="ClinicFlow"
                                className="h-8 w-8"
                            />
                            <img
                                src="/images/clinicflow-text-logo.png"
                                alt="ClinicFlow"
                                className="hidden h-6 sm:block dark:brightness-0 dark:invert"
                            />
                        </Link>
                        <nav className="flex items-center gap-3">
                            <Button asChild variant="ghost" size="sm">
                                <Link href={login()}>Sign In</Link>
                            </Button>
                            {canRegister && (
                                <Button
                                    asChild
                                    size="sm"
                                    className="bg-[var(--clinicflow-orange)] text-white hover:bg-[var(--clinicflow-orange)]/90"
                                >
                                    <Link href={register()}>Register</Link>
                                </Button>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex flex-1 flex-col">
                    <section className="flex flex-1 items-center justify-center px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                        <div className="w-full max-w-6xl">
                            <div className="mb-16 text-center">
                                <h1 className="mb-6 text-4xl font-bold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                                    Streamline Your Clinic Operations
                                </h1>
                                <p className="mx-auto mb-10 max-w-2xl text-lg text-muted-foreground sm:text-xl">
                                    Comprehensive patient management,
                                    appointment scheduling, and exam room
                                    coordination for healthcare professionals.
                                </p>
                                <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                    <Button
                                        asChild
                                        size="lg"
                                        className="bg-[var(--clinicflow-teal)] px-8 text-white hover:bg-[var(--clinicflow-teal)]/90"
                                    >
                                        <Link href={login()}>Sign In</Link>
                                    </Button>
                                    {canRegister && (
                                        <Button
                                            asChild
                                            size="lg"
                                            variant="outline"
                                            className="border-2 border-[var(--clinicflow-dark)] px-8 text-[var(--clinicflow-dark)] hover:bg-[var(--clinicflow-dark)] hover:text-white"
                                        >
                                            <Link href={register()}>
                                                Create Account
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <div className="mt-20 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                {features.map((feature) => {
                                    const Icon = feature.icon;
                                    return (
                                        <Card
                                            key={feature.title}
                                            className="border-2 border-border transition-all duration-300 hover:-translate-y-1 hover:border-[var(--clinicflow-teal)]/30 hover:shadow-lg"
                                        >
                                            <CardContent className="p-6 text-center">
                                                <div className="mb-4 flex justify-center">
                                                    <div
                                                        className={`flex size-14 items-center justify-center rounded-xl bg-background ${feature.color}`}
                                                    >
                                                        <Icon className="size-7" />
                                                    </div>
                                                </div>
                                                <h3 className="mb-2 text-lg font-semibold text-foreground">
                                                    {feature.title}
                                                </h3>
                                                <p className="text-sm leading-relaxed text-muted-foreground">
                                                    {feature.description}
                                                </p>
                                            </CardContent>
                                        </Card>
                                    );
                                })}
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border bg-card/30 py-6">
                    <div className="container mx-auto px-4 text-center text-sm text-muted-foreground sm:px-6 lg:px-8">
                        <p>
                            ClinicFlow - Healthcare clinic management system.
                            Designed for demonstration and educational purposes.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
