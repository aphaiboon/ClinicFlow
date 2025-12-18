import PatientLayout from '@/layouts/patient-layout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { User, Mail, MapPin } from 'lucide-react';

interface Patient {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    date_of_birth: string;
    medical_record_number: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
}

interface ProfileShowProps {
    patient: Patient;
    editableFields?: string[];
}

export default function PatientProfileShow({
    patient,
}: ProfileShowProps) {
    return (
        <PatientLayout title="My Profile">
            <Head title="My Profile" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">My Profile</h1>
                        <p className="mt-2 text-muted-foreground">
                            View your profile information
                        </p>
                    </div>
                    <Link href="/patient/profile/edit">
                        <Button>Edit Profile</Button>
                    </Link>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="size-5" />
                                Personal Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Name</p>
                                <p className="text-lg font-semibold">
                                    {patient.first_name} {patient.last_name}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Medical Record Number</p>
                                <p className="text-lg font-semibold">{patient.medical_record_number}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Date of Birth</p>
                                <p className="text-lg font-semibold">
                                    {new Date(patient.date_of_birth).toLocaleDateString()}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Mail className="size-5" />
                                Contact Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Email</p>
                                <p className="text-lg font-semibold">{patient.email}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Phone</p>
                                <p className="text-lg font-semibold">{patient.phone}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="size-5" />
                                Address
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-lg">
                                {patient.address_line_1}
                                {patient.address_line_2 && `, ${patient.address_line_2}`}
                            </p>
                            <p className="text-lg">
                                {patient.city}, {patient.state} {patient.postal_code}
                            </p>
                            <p className="text-lg">{patient.country}</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </PatientLayout>
    );
}

