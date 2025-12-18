import { login } from '@/routes';
import { store } from '@/routes/organization/register';
import { Form, Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

export default function OrganizationRegister() {
    return (
        <AuthLayout
            title="Create your clinic organization"
            description="Enter your clinic and account details to get started"
        >
            <Head title="Register Organization" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="space-y-6">
                            <div>
                                <h2 className="mb-4 text-lg font-semibold text-foreground">
                                    Clinic Information
                                </h2>
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Clinic Name *
                                        </Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            name="name"
                                            placeholder="Your Clinic Name"
                                        />
                                        <InputError
                                            message={errors.name}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            Clinic Email
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            tabIndex={2}
                                            name="email"
                                            placeholder="clinic@example.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            type="tel"
                                            tabIndex={3}
                                            name="phone"
                                            placeholder="555-1234"
                                        />
                                        <InputError message={errors.phone} />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h2 className="mb-4 text-lg font-semibold text-foreground">
                                    Your Account
                                </h2>
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="user_name">
                                            Your Name *
                                        </Label>
                                        <Input
                                            id="user_name"
                                            type="text"
                                            required
                                            tabIndex={4}
                                            autoComplete="name"
                                            name="user_name"
                                            placeholder="Full name"
                                        />
                                        <InputError
                                            message={errors.user_name}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="user_email">
                                            Email Address *
                                        </Label>
                                        <Input
                                            id="user_email"
                                            type="email"
                                            required
                                            tabIndex={5}
                                            autoComplete="email"
                                            name="user_email"
                                            placeholder="email@example.com"
                                        />
                                        <InputError
                                            message={errors.user_email}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Password *
                                        </Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            required
                                            tabIndex={6}
                                            autoComplete="new-password"
                                            name="password"
                                            placeholder="Password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            Confirm Password *
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            required
                                            tabIndex={7}
                                            autoComplete="new-password"
                                            name="password_confirmation"
                                            placeholder="Confirm password"
                                        />
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={8}
                                data-test="register-organization-button"
                            >
                                {processing && <Spinner />}
                                Create Organization
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} tabIndex={9}>
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
