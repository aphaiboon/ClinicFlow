import PatientLayout from '@/layouts/patient-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { useState } from 'react';

interface Patient {
    id: number;
    email: string;
    phone: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
}

interface ProfileEditProps {
    patient: Patient;
    editableFields?: string[];
}

export default function PatientProfileEdit({
    patient,
}: ProfileEditProps) {
    const [formData, setFormData] = useState({
        email: patient.email || '',
        phone: patient.phone || '',
        address_line_1: patient.address_line_1 || '',
        address_line_2: patient.address_line_2 || '',
        city: patient.city || '',
        state: patient.state || '',
        postal_code: patient.postal_code || '',
        country: patient.country || 'US',
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        router.put('/patient/profile', formData, {
            onSuccess: () => {
                router.visit('/patient/profile');
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
                setIsSubmitting(false);
            },
            onFinish: () => {
                setIsSubmitting(false);
            },
        });
    };

    return (
        <PatientLayout title="Edit Profile">
            <Head title="Edit Profile" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Edit Profile</h1>
                        <p className="mt-2 text-muted-foreground">
                            Update your contact information
                        </p>
                    </div>
                    <Link href="/patient/profile">
                        <Button variant="outline">Cancel</Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Contact Information</CardTitle>
                        <CardDescription>
                            Update your email and phone number
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={formData.email}
                                        onChange={(e) =>
                                            setFormData({ ...formData, email: e.target.value })
                                        }
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={formData.phone}
                                        onChange={(e) =>
                                            setFormData({ ...formData, phone: e.target.value })
                                        }
                                        required
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address_line_1">Address Line 1</Label>
                                <Input
                                    id="address_line_1"
                                    value={formData.address_line_1}
                                    onChange={(e) =>
                                        setFormData({ ...formData, address_line_1: e.target.value })
                                    }
                                    required
                                />
                                <InputError message={errors.address_line_1} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address_line_2">Address Line 2 (Optional)</Label>
                                <Input
                                    id="address_line_2"
                                    value={formData.address_line_2}
                                    onChange={(e) =>
                                        setFormData({ ...formData, address_line_2: e.target.value })
                                    }
                                />
                                <InputError message={errors.address_line_2} />
                            </div>

                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="city">City</Label>
                                    <Input
                                        id="city"
                                        value={formData.city}
                                        onChange={(e) =>
                                            setFormData({ ...formData, city: e.target.value })
                                        }
                                        required
                                    />
                                    <InputError message={errors.city} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="state">State</Label>
                                    <Input
                                        id="state"
                                        value={formData.state}
                                        onChange={(e) =>
                                            setFormData({ ...formData, state: e.target.value })
                                        }
                                        required
                                    />
                                    <InputError message={errors.state} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="postal_code">Postal Code</Label>
                                    <Input
                                        id="postal_code"
                                        value={formData.postal_code}
                                        onChange={(e) =>
                                            setFormData({ ...formData, postal_code: e.target.value })
                                        }
                                        required
                                    />
                                    <InputError message={errors.postal_code} />
                                </div>
                            </div>

                            <div className="flex gap-4 pt-4">
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Saving...' : 'Save Changes'}
                                </Button>
                                <Link href="/patient/profile">
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </PatientLayout>
    );
}

