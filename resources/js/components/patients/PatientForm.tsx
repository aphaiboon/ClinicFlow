import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Form } from '@inertiajs/react';
import { type Patient } from '@/types';
import { type RouteDefinition } from '@/wayfinder';
import { useState } from 'react';

interface PatientFormProps {
    patient?: Patient;
    route: RouteDefinition<'post'> | RouteDefinition<'put'>;
    processing?: boolean;
}

export function PatientForm({ patient, route, processing = false }: PatientFormProps) {
    const [gender, setGender] = useState(patient?.gender || '');

    return (
        <Form
            {...route.form()}
            className="space-y-6"
        >
            {({ processing: formProcessing, errors }) => (
                <>
                    <input type="hidden" name="gender" value={gender} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="first_name">First Name *</Label>
                            <Input
                                id="first_name"
                                name="first_name"
                                defaultValue={patient?.first_name}
                                required
                                autoComplete="given-name"
                            />
                            <InputError message={errors.first_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="last_name">Last Name *</Label>
                            <Input
                                id="last_name"
                                name="last_name"
                                defaultValue={patient?.last_name}
                                required
                                autoComplete="family-name"
                            />
                            <InputError message={errors.last_name} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="date_of_birth">Date of Birth *</Label>
                            <Input
                                id="date_of_birth"
                                type="date"
                                name="date_of_birth"
                                defaultValue={patient?.date_of_birth ? new Date(patient.date_of_birth).toISOString().split('T')[0] : ''}
                                required
                                max={new Date().toISOString().split('T')[0]}
                            />
                            <InputError message={errors.date_of_birth} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="gender">Gender *</Label>
                            <Select value={gender} onValueChange={setGender} required>
                                <SelectTrigger id="gender">
                                    <SelectValue placeholder="Select gender" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="male">Male</SelectItem>
                                    <SelectItem value="female">Female</SelectItem>
                                    <SelectItem value="other">Other</SelectItem>
                                    <SelectItem value="prefer_not_to_say">Prefer not to say</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.gender} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input
                                id="phone"
                                type="tel"
                                name="phone"
                                defaultValue={patient?.phone}
                                autoComplete="tel"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                defaultValue={patient?.email}
                                autoComplete="email"
                            />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="address_line_1">Address Line 1</Label>
                        <Input
                            id="address_line_1"
                            name="address_line_1"
                            defaultValue={patient?.address_line_1}
                            autoComplete="street-address"
                        />
                        <InputError message={errors.address_line_1} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="address_line_2">Address Line 2</Label>
                        <Input
                            id="address_line_2"
                            name="address_line_2"
                            defaultValue={patient?.address_line_2}
                            autoComplete="address-line2"
                        />
                        <InputError message={errors.address_line_2} />
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="city">City</Label>
                            <Input
                                id="city"
                                name="city"
                                defaultValue={patient?.city}
                                autoComplete="address-level2"
                            />
                            <InputError message={errors.city} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="state">State</Label>
                            <Input
                                id="state"
                                name="state"
                                defaultValue={patient?.state}
                                autoComplete="address-level1"
                            />
                            <InputError message={errors.state} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="postal_code">Postal Code</Label>
                            <Input
                                id="postal_code"
                                name="postal_code"
                                defaultValue={patient?.postal_code}
                                autoComplete="postal-code"
                            />
                            <InputError message={errors.postal_code} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="country">Country</Label>
                        <Input
                            id="country"
                            name="country"
                            defaultValue={patient?.country}
                            autoComplete="country-name"
                        />
                        <InputError message={errors.country} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={formProcessing || processing}>
                            {formProcessing || processing ? 'Saving...' : patient ? 'Update Patient' : 'Create Patient'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

