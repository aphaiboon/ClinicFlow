import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type Patient } from '@/types';
import { Link } from '@inertiajs/react';

interface PatientCardProps {
    patient: Patient;
}

export function PatientCard({ patient }: PatientCardProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    <Link
                        href={`/patients/${patient.id}`}
                        className="hover:underline"
                    >
                        {patient.first_name} {patient.last_name}
                    </Link>
                </CardTitle>
                <CardDescription>
                    {patient.medical_record_number}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-2 text-sm">
                    {patient.email && <div>Email: {patient.email}</div>}
                    {patient.phone && <div>Phone: {patient.phone}</div>}
                    {patient.date_of_birth && (
                        <div>
                            DOB:{' '}
                            {new Date(
                                patient.date_of_birth,
                            ).toLocaleDateString()}
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
