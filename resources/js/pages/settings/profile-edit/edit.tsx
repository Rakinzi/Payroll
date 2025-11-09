import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import Heading from '@/components/heading';
import {
    useUpdateProfile,
    useUpdateAvatar,
    useUpdateSignature,
    useUpdatePassword,
    useUpdatePayslipPassword,
    useUpdateBankDetails,
} from '@/hooks/queries/use-profile-settings';
import { useProfileSettingsStore } from '@/stores/profile-settings-store';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Department, Position } from '@/types';
import { Head } from '@inertiajs/react';
import { User, Building2, ImageIcon, Lock } from 'lucide-react';
import { useState, useRef, ChangeEvent } from 'react';
import { useToast } from '@/hooks/use-toast';

interface ProfilePreferences {
    theme?: string;
    notifications?: boolean;
    [key: string]: unknown;
}

interface Props {
    profile: {
        profile_id: number;
        user_id: number;
        avatar_path: string | null;
        signature_path: string | null;
        avatar_url: string | null;
        signature_url: string | null;
        preferences: ProfilePreferences | null;
    };
    employee: {
        id: string;
        firstname: string;
        surname: string;
        nationality?: string;
        nat_id: string;
        gender: 'Male' | 'Female';
        dob: string;
        marital_status?: string;
        home_address: string;
        city: string;
        country: string;
        phone_number: string;
        personal_email_address?: string;
        religion?: string;
        drivers_licence_id?: string;
        drivers_licence_class?: number;
        passport_id?: string;
        department_id: string;
        position_id: string;
        payment_method: string;
        payment_basis: string;
        title: string;
    } | null;
    departments: Department[];
    positions: Position[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Profile Settings', href: '/settings/profile' },
];

export default function ProfileEdit({ profile, employee, departments, positions }: Props) {
    const { toast } = useToast();
    const { activeTab, setActiveTab, avatarPreview, setAvatarPreview } = useProfileSettingsStore();

    const updateProfile = useUpdateProfile();
    const updateAvatar = useUpdateAvatar();
    const updateSignature = useUpdateSignature();
    const updatePassword = useUpdatePassword();
    const updatePayslipPassword = useUpdatePayslipPassword();
    const updateBankDetails = useUpdateBankDetails();

    const [personalForm, setPersonalForm] = useState(employee || {});
    const [passwordForm, setPasswordForm] = useState({
        old_password: '',
        new_password: '',
        new_password_confirmation: '',
    });
    const [payslipPasswordForm, setPayslipPasswordForm] = useState({
        old_password: '',
        new_password: '',
        new_password_confirmation: '',
    });
    const [bankForm, setBankForm] = useState({
        bank_name: employee?.bank_name || '',
        bank_account_number: employee?.bank_account_number || '',
        bank_branch: employee?.bank_branch || '',
        bank_account_name: employee?.bank_account_name || '',
    });

    const avatarInputRef = useRef<HTMLInputElement>(null);
    const signatureCanvasRef = useRef<HTMLCanvasElement>(null);

    const handlePersonalSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        updateProfile.mutate(personalForm, {
            onSuccess: () => {
                toast({ title: 'Success', description: 'Profile updated successfully' });
            },
            onError: (error: Error) => {
                toast({
                    title: 'Error',
                    description: error?.message || 'Failed to update profile',
                    variant: 'destructive',
                });
            },
        });
    };

    const handleAvatarChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validate file size (128KB)
            if (file.size > 128000) {
                toast({
                    title: 'Error',
                    description: 'Avatar file size must be less than 128KB',
                    variant: 'destructive',
                });
                return;
            }

            // Create preview
            const reader = new FileReader();
            reader.onloadend = () => {
                setAvatarPreview(reader.result as string);
            };
            reader.readAsDataURL(file);

            // Upload avatar
            updateAvatar.mutate(
                { avatar: file },
                {
                    onSuccess: () => {
                        toast({ title: 'Success', description: 'Avatar updated successfully' });
                    },
                    onError: (error: Error) => {
                        toast({
                            title: 'Error',
                            description: error?.message || 'Failed to update avatar',
                            variant: 'destructive',
                        });
                    },
                }
            );
        }
    };

    const handlePasswordSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        updatePassword.mutate(passwordForm, {
            onSuccess: () => {
                toast({ title: 'Success', description: 'Password updated successfully' });
                setPasswordForm({
                    old_password: '',
                    new_password: '',
                    new_password_confirmation: '',
                });
            },
            onError: (error: Error) => {
                toast({
                    title: 'Error',
                    description: error?.message || 'Failed to update password',
                    variant: 'destructive',
                });
            },
        });
    };

    const handlePayslipPasswordSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        updatePayslipPassword.mutate(payslipPasswordForm, {
            onSuccess: () => {
                toast({ title: 'Success', description: 'Payslip password updated successfully' });
                setPayslipPasswordForm({
                    old_password: '',
                    new_password: '',
                    new_password_confirmation: '',
                });
            },
            onError: (error: Error) => {
                toast({
                    title: 'Error',
                    description: error?.message || 'Failed to update payslip password',
                    variant: 'destructive',
                });
            },
        });
    };

    const handleBankSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        updateBankDetails.mutate(bankForm, {
            onSuccess: () => {
                toast({ title: 'Success', description: 'Bank details updated successfully' });
            },
            onError: (error: Error) => {
                toast({
                    title: 'Error',
                    description: error?.message || 'Failed to update bank details',
                    variant: 'destructive',
                });
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile Settings" />

            <div className="space-y-6">
                <div>
                    <Heading>Profile Settings</Heading>
                    <p className="text-muted-foreground mt-1">
                        Manage your personal information, bank details, and security settings
                    </p>
                </div>

                <Tabs value={activeTab} onValueChange={(value: string) => setActiveTab(value)}>
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="personal">
                            <User className="mr-2 h-4 w-4" />
                            Personal Info
                        </TabsTrigger>
                        <TabsTrigger value="bank">
                            <Building2 className="mr-2 h-4 w-4" />
                            Bank Details
                        </TabsTrigger>
                        <TabsTrigger value="avatar">
                            <ImageIcon className="mr-2 h-4 w-4" />
                            Avatar
                        </TabsTrigger>
                        <TabsTrigger value="passwords">
                            <Lock className="mr-2 h-4 w-4" />
                            Security
                        </TabsTrigger>
                    </TabsList>

                    {/* Personal Information Tab */}
                    <TabsContent value="personal">
                        <Card>
                            <CardHeader>
                                <CardTitle>Personal Information</CardTitle>
                                <CardDescription>
                                    Update your personal details and contact information
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handlePersonalSubmit}>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label>Title *</Label>
                                            <Select
                                                value={personalForm.title}
                                                onValueChange={(value) =>
                                                    setPersonalForm({ ...personalForm, title: value })
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Hon">Hon</SelectItem>
                                                    <SelectItem value="Dr">Dr</SelectItem>
                                                    <SelectItem value="Mr">Mr</SelectItem>
                                                    <SelectItem value="Mrs">Mrs</SelectItem>
                                                    <SelectItem value="Ms">Ms</SelectItem>
                                                    <SelectItem value="Sir">Sir</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label>First Name *</Label>
                                            <Input
                                                value={personalForm.firstname}
                                                onChange={(e) =>
                                                    setPersonalForm({
                                                        ...personalForm,
                                                        firstname: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Surname *</Label>
                                            <Input
                                                value={personalForm.surname}
                                                onChange={(e) =>
                                                    setPersonalForm({
                                                        ...personalForm,
                                                        surname: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>National ID *</Label>
                                            <Input
                                                value={personalForm.nat_id}
                                                onChange={(e) =>
                                                    setPersonalForm({
                                                        ...personalForm,
                                                        nat_id: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Gender *</Label>
                                            <Select
                                                value={personalForm.gender}
                                                onValueChange={(value: string) =>
                                                    setPersonalForm({ ...personalForm, gender: value })
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Male">Male</SelectItem>
                                                    <SelectItem value="Female">Female</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Date of Birth *</Label>
                                            <Input
                                                type="date"
                                                value={personalForm.dob}
                                                onChange={(e) =>
                                                    setPersonalForm({ ...personalForm, dob: e.target.value })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Phone Number *</Label>
                                            <Input
                                                value={personalForm.phone_number}
                                                onChange={(e) =>
                                                    setPersonalForm({
                                                        ...personalForm,
                                                        phone_number: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Email</Label>
                                            <Input
                                                type="email"
                                                value={personalForm.personal_email_address || ''}
                                                onChange={(e) =>
                                                    setPersonalForm({
                                                        ...personalForm,
                                                        personal_email_address: e.target.value,
                                                    })
                                                }
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>City *</Label>
                                            <Input
                                                value={personalForm.city}
                                                onChange={(e) =>
                                                    setPersonalForm({ ...personalForm, city: e.target.value })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Country *</Label>
                                            <Input
                                                value={personalForm.country}
                                                onChange={(e) =>
                                                    setPersonalForm({
                                                        ...personalForm,
                                                        country: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2 md:col-span-2">
                                            <Label>Home Address *</Label>
                                            <Input
                                                value={personalForm.home_address}
                                                onChange={(e) =>
                                                    setPersonalForm({
                                                        ...personalForm,
                                                        home_address: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div className="mt-6">
                                        <Button type="submit" disabled={updateProfile.isPending}>
                                            {updateProfile.isPending
                                                ? 'Updating...'
                                                : 'Update Personal Information'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Bank Details Tab */}
                    <TabsContent value="bank">
                        <Card>
                            <CardHeader>
                                <CardTitle>Bank Details</CardTitle>
                                <CardDescription>Manage your banking information</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleBankSubmit}>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label>Bank Name *</Label>
                                            <Input
                                                value={bankForm.bank_name}
                                                onChange={(e) =>
                                                    setBankForm({ ...bankForm, bank_name: e.target.value })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Account Number *</Label>
                                            <Input
                                                value={bankForm.bank_account_number}
                                                onChange={(e) =>
                                                    setBankForm({
                                                        ...bankForm,
                                                        bank_account_number: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Branch *</Label>
                                            <Input
                                                value={bankForm.bank_branch}
                                                onChange={(e) =>
                                                    setBankForm({ ...bankForm, bank_branch: e.target.value })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Account Name *</Label>
                                            <Input
                                                value={bankForm.bank_account_name}
                                                onChange={(e) =>
                                                    setBankForm({
                                                        ...bankForm,
                                                        bank_account_name: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div className="mt-6">
                                        <Button type="submit" disabled={updateBankDetails.isPending}>
                                            {updateBankDetails.isPending
                                                ? 'Updating...'
                                                : 'Update Bank Details'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Avatar Tab */}
                    <TabsContent value="avatar">
                        <Card>
                            <CardHeader>
                                <CardTitle>Profile Picture</CardTitle>
                                <CardDescription>
                                    Upload your profile picture (Max 128KB, JPG or PNG)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div className="flex items-center gap-4">
                                        <div className="flex-shrink-0">
                                            {avatarPreview || profile.avatar_url ? (
                                                <img
                                                    src={avatarPreview || profile.avatar_url || ''}
                                                    alt="Profile"
                                                    className="h-24 w-24 rounded-full object-cover border-2"
                                                />
                                            ) : (
                                                <div className="h-24 w-24 rounded-full bg-muted flex items-center justify-center border-2">
                                                    <User className="h-12 w-12 text-muted-foreground" />
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex-1">
                                            <Input
                                                ref={avatarInputRef}
                                                type="file"
                                                accept="image/png,image/jpeg,image/jpg,image/JPG,image/PNG"
                                                onChange={handleAvatarChange}
                                            />
                                            <p className="text-sm text-muted-foreground mt-2">
                                                Supported files: JPG, PNG (Max 128KB)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Security Tab */}
                    <TabsContent value="passwords">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* System Password */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>System Password</CardTitle>
                                    <CardDescription>
                                        Change your system login password
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handlePasswordSubmit}>
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label>Current Password</Label>
                                                <Input
                                                    type="password"
                                                    value={passwordForm.old_password}
                                                    onChange={(e) =>
                                                        setPasswordForm({
                                                            ...passwordForm,
                                                            old_password: e.target.value,
                                                        })
                                                    }
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>New Password</Label>
                                                <Input
                                                    type="password"
                                                    value={passwordForm.new_password}
                                                    onChange={(e) =>
                                                        setPasswordForm({
                                                            ...passwordForm,
                                                            new_password: e.target.value,
                                                        })
                                                    }
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>Confirm New Password</Label>
                                                <Input
                                                    type="password"
                                                    value={passwordForm.new_password_confirmation}
                                                    onChange={(e) =>
                                                        setPasswordForm({
                                                            ...passwordForm,
                                                            new_password_confirmation: e.target.value,
                                                        })
                                                    }
                                                    required
                                                />
                                            </div>

                                            <Button type="submit" disabled={updatePassword.isPending}>
                                                {updatePassword.isPending
                                                    ? 'Updating...'
                                                    : 'Change System Password'}
                                            </Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>

                            {/* Payslip Password */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Payslip Password</CardTitle>
                                    <CardDescription>
                                        Change your payslip access password
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handlePayslipPasswordSubmit}>
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label>Current Payslip Password</Label>
                                                <Input
                                                    type="password"
                                                    value={payslipPasswordForm.old_password}
                                                    onChange={(e) =>
                                                        setPayslipPasswordForm({
                                                            ...payslipPasswordForm,
                                                            old_password: e.target.value,
                                                        })
                                                    }
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>New Payslip Password</Label>
                                                <Input
                                                    type="password"
                                                    value={payslipPasswordForm.new_password}
                                                    onChange={(e) =>
                                                        setPayslipPasswordForm({
                                                            ...payslipPasswordForm,
                                                            new_password: e.target.value,
                                                        })
                                                    }
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>Confirm New Payslip Password</Label>
                                                <Input
                                                    type="password"
                                                    value={payslipPasswordForm.new_password_confirmation}
                                                    onChange={(e) =>
                                                        setPayslipPasswordForm({
                                                            ...payslipPasswordForm,
                                                            new_password_confirmation: e.target.value,
                                                        })
                                                    }
                                                    required
                                                />
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={updatePayslipPassword.isPending}
                                            >
                                                {updatePayslipPassword.isPending
                                                    ? 'Updating...'
                                                    : 'Change Payslip Password'}
                                            </Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
