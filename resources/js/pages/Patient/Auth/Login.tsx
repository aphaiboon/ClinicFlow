import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface LoginProps {
    status?: string;
    prefilledEmail?: string;
}

export default function PatientLogin({ status, prefilledEmail }: LoginProps) {
    const [email, setEmail] = useState(prefilledEmail || '');
    const [processing, setProcessing] = useState(false);

    const isEmailValid = email.trim() !== '' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!isEmailValid) {
            return;
        }

        setProcessing(true);
        router.post(
            '/patient/login',
            { email },
            {
                onFinish: () => {
                    setProcessing(false);
                },
            }
        );
    };

    return (
        <AuthLayout
            title="Patient Portal Login"
            description="Enter your email to receive a secure login link"
        >
            <Head title="Patient Login" />

            <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                <div className="grid gap-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        required
                        autoFocus
                        autoComplete="email"
                        placeholder="patient@example.com"
                    />
                    <InputError message={undefined} />
                </div>

                <Button
                    type="submit"
                    className="w-full"
                    disabled={!isEmailValid || processing}
                >
                    {processing && <Spinner />}
                    Send Login Link
                </Button>

                <div className="text-center text-sm text-muted-foreground">
                    Are you staff?{' '}
                    <TextLink href="/login" tabIndex={5}>
                        Staff Login
                    </TextLink>
                </div>
            </form>

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </AuthLayout>
    );
}

