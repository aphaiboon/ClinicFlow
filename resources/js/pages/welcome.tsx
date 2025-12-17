import { login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Calendar, Building, Users, Activity } from 'lucide-react';

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
            color: 'text-[#1bc3bb]',
        },
        {
            icon: Calendar,
            title: 'Appointment Scheduling',
            description: 'Schedule, reschedule, and manage patient appointments efficiently',
            color: 'text-[#F1903C]',
        },
        {
            icon: Building,
            title: 'Exam Room Management',
            description: 'Assign patients to exam rooms and track room availability',
            color: 'text-[#806954]',
        },
        {
            icon: Activity,
            title: 'Audit & Compliance',
            description: 'Complete audit trails and compliance-ready logging for healthcare operations',
            color: 'text-[#F2B064]',
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
                                className="h-6 hidden sm:block"
                            />
                        </Link>
                        <nav className="flex items-center gap-3">
                            <Button asChild variant="ghost" size="sm">
                                <Link href={login()}>Sign In</Link>
                            </Button>
                            {canRegister && (
                                <Button asChild size="sm" className="bg-[--clinicflow-orange] hover:bg-[--clinicflow-orange]/90 text-white">
                                    <Link href={register()}>Register</Link>
                                </Button>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex flex-1 flex-col">
                    <section className="flex flex-1 items-center justify-center px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                        <div className="w-full max-w-6xl">
                            <div className="text-center mb-16">
                                <div className="mb-8 flex justify-center">
                                    <div className="relative">
                                        <div className="absolute inset-0 bg-[#1bc3bb]/20 blur-3xl rounded-full" />
                                        <img
                                            src="/images/clinicflow-icon-logo.png"
                                            alt="ClinicFlow"
                                            className="relative h-24 w-24 sm:h-32 sm:w-32"
                                        />
                                    </div>
                                </div>
                                <h1 className="mb-4 text-5xl font-bold tracking-tight sm:text-6xl lg:text-7xl bg-gradient-to-r from-[#323d47] to-[#1bc3bb] bg-clip-text text-transparent">
                                    ClinicFlow
                                </h1>
                                <p className="mb-6 text-2xl font-medium text-foreground sm:text-3xl">
                                    Clinic Management System
                                </p>
                                <p className="mx-auto mb-10 max-w-2xl text-lg text-muted-foreground sm:text-xl">
                                    Streamline your clinic operations with comprehensive patient management,
                                    appointment scheduling, and exam room coordination—all designed for healthcare
                                    professionals.
                                </p>
                                <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                    <Button asChild size="lg" className="bg-[#1bc3bb] hover:bg-[#1bc3bb]/90 text-white px-8">
                                        <Link href={login()}>Sign In</Link>
                                    </Button>
                                    {canRegister && (
                                        <Button asChild size="lg" variant="outline" className="border-2 border-[#323d47] text-[#323d47] hover:bg-[#323d47] hover:text-white px-8">
                                            <Link href={register()}>Create Account</Link>
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
                                            className="border-2 border-border hover:border-[#1bc3bb]/30 transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
                                        >
                                            <CardContent className="p-6 text-center">
                                                <div className="mb-4 flex justify-center">
                                                    <div className={`flex size-14 items-center justify-center rounded-xl bg-background ${feature.color}`}>
                                                        <Icon className="size-7" />
                                                    </div>
                                                </div>
                                                <h3 className="mb-2 text-lg font-semibold text-foreground">{feature.title}</h3>
                                                <p className="text-sm text-muted-foreground leading-relaxed">
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
                            ClinicFlow - Healthcare clinic management system. Designed for demonstration and
                            educational purposes.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
