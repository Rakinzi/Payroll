import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';

interface CostCenter {
    id: string;
    center_name: string;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    costCenters: CostCenter[];
}

export default function Login({
    status,
    canResetPassword,
    canRegister,
    costCenters = [],
}: LoginProps) {
    return (
        <>
            <Head title="Log in" />
            <div className="grid min-h-svh lg:grid-cols-2">
                <div className="flex flex-col gap-4 p-6 md:p-10">
                    <div className="flex justify-center gap-2 md:justify-start">
                        <a href="/" className="flex items-center gap-2 font-medium">
                            <div className="bg-primary text-primary-foreground flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon className="h-6 w-6" />
                            </div>
                            Lorimak Payroll
                        </a>
                    </div>
                    <div className="flex flex-1 items-center justify-center">
                        <div className="w-full max-w-md">
                            <Form
                                action="/login"
                                method="post"
                                resetOnSuccess={['password']}
                                className="flex flex-col gap-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="flex flex-col gap-1 text-center">
                                            <h1 className="text-2xl font-bold">Log in to your account</h1>
                                            <p className="text-muted-foreground text-sm text-balance">
                                                Enter your email and password below to log in
                                            </p>
                                        </div>

                                        {status && (
                                            <div className="text-center text-sm font-medium text-green-600">
                                                {status}
                                            </div>
                                        )}

                                        <div className="grid gap-6">
                                            <div className="grid gap-2">
                                                <Label htmlFor="email">Email address</Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    name="email"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="email"
                                                    placeholder="email@example.com"
                                                />
                                                <InputError message={errors.email} />
                                            </div>

                                            <div className="grid gap-2">
                                                <div className="flex items-center">
                                                    <Label htmlFor="password">Password</Label>
                                                    {canResetPassword && (
                                                        <TextLink
                                                            href={request()}
                                                            className="ml-auto text-sm"
                                                            tabIndex={5}
                                                        >
                                                            Forgot password?
                                                        </TextLink>
                                                    )}
                                                </div>
                                                <Input
                                                    id="password"
                                                    type="password"
                                                    name="password"
                                                    required
                                                    tabIndex={2}
                                                    autoComplete="current-password"
                                                    placeholder="Password"
                                                />
                                                <InputError message={errors.password} />
                                            </div>

                                            {costCenters && costCenters.length > 0 && (
                                                <div className="grid gap-2">
                                                    <Label htmlFor="center_id">Cost Center</Label>
                                                    <select
                                                        id="center_id"
                                                        name="center_id"
                                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                    >
                                                        <option value="">Super Admin Login</option>
                                                        {costCenters.map((center) => (
                                                            <option key={center.id} value={center.id}>
                                                                {center.center_name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError message={errors.center_id} />
                                                    <p className="text-xs text-muted-foreground">
                                                        Leave empty to log in as super admin
                                                    </p>
                                                </div>
                                            )}

                                            <div className="flex items-center space-x-3">
                                                <Checkbox
                                                    id="remember"
                                                    name="remember"
                                                    tabIndex={3}
                                                />
                                                <Label htmlFor="remember">Remember me</Label>
                                            </div>

                                            <Button
                                                type="submit"
                                                className="w-full"
                                                tabIndex={4}
                                                disabled={processing}
                                                data-test="login-button"
                                            >
                                                {processing && <Spinner />}
                                                Log in
                                            </Button>
                                        </div>

                                        {canRegister && (
                                            <div className="text-center text-sm text-muted-foreground">
                                                Don't have an account?{' '}
                                                <TextLink href={register()} tabIndex={5}>
                                                    Sign up
                                                </TextLink>
                                            </div>
                                        )}
                                    </>
                                )}
                            </Form>
                        </div>
                    </div>
                </div>
                <div className="bg-muted relative hidden lg:block">
                    <img
                        src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?q=80&w=2670&auto=format&fit=crop"
                        alt="Lorimak Payroll System"
                        className="absolute inset-0 h-full w-full object-cover dark:brightness-[0.2] dark:grayscale"
                    />
                </div>
            </div>
        </>
    );
}
