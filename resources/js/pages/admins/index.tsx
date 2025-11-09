import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    type Admin,
    type CostCenter,
    type CreateAdminData,
    type ResetPasswordData,
    type UpdateAdminData,
    useCreateAdmin,
    useDeleteAdmin,
    useResetAdminPassword,
    useUpdateAdmin,
} from '@/hooks/queries/use-admins';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type PaginatedData, type PaginationLink } from '@/types';
import { Head, router } from '@inertiajs/react';
import { KeyRound, MoreHorizontal, Pencil, Plus, Shield, Trash2, UserCog } from 'lucide-react';
import { useEffect, useState } from 'react';
import { create } from 'zustand';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Admin Management', href: '/admins' },
];

interface AdminDialogStore {
    isOpen: boolean;
    admin: Admin | null;
    mode: 'create' | 'edit';
    open: (mode: 'create' | 'edit', admin?: Admin) => void;
    close: () => void;
}

const useAdminDialog = create<AdminDialogStore>((set) => ({
    isOpen: false,
    admin: null,
    mode: 'create',
    open: (mode, admin) => set({ isOpen: true, mode, admin: admin || null }),
    close: () => set({ isOpen: false, admin: null }),
}));

interface PasswordDialogStore {
    isOpen: boolean;
    admin: Admin | null;
    open: (admin: Admin) => void;
    close: () => void;
}

const usePasswordDialog = create<PasswordDialogStore>((set) => ({
    isOpen: false,
    admin: null,
    open: (admin) => set({ isOpen: true, admin }),
    close: () => set({ isOpen: false, admin: null }),
}));

interface Props {
    admins: PaginatedData<Admin>;
    costCenters: CostCenter[];
    filters?: {
        search?: string;
        cost_center?: string;
    };
    currentUserId: string;
}

export default function AdminsPage({ admins, costCenters, filters, currentUserId }: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [costCenterFilter, setCostCenterFilter] = useState(filters?.cost_center ?? 'all');
    const deleteMutation = useDeleteAdmin();
    const { open: openAdminDialog } = useAdminDialog();
    const { open: openPasswordDialog } = usePasswordDialog();

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(
            '/admins',
            {
                search: value,
                cost_center: costCenterFilter !== 'all' ? costCenterFilter : undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const handleCostCenterFilter = (value: string) => {
        setCostCenterFilter(value);
        router.get(
            '/admins',
            {
                search,
                cost_center: value !== 'all' ? value : undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const handleDelete = (admin: Admin) => {
        if (admin.id === currentUserId) {
            alert('You cannot delete your own admin account.');
            return;
        }

        if (confirm(`Are you sure you want to delete admin account for ${admin.name}?`)) {
            deleteMutation.mutate(admin.id);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Management" />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Admin Management</h1>
                        <p className="text-muted-foreground">
                            Manage administrative users and their cost center access
                        </p>
                    </div>
                    <Button onClick={() => openAdminDialog('create')}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Admin
                    </Button>
                </div>

                {/* Info Alert */}
                <div className="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                    <p className="text-sm text-blue-900 dark:text-blue-100">
                        <strong>Note:</strong> Only Super Administrators can manage admin accounts.
                        Cost Center Admins have limited access to their assigned cost center only.
                    </p>
                </div>

                {/* Search & Filters */}
                <div className="flex gap-4">
                    <Input
                        placeholder="Search by name or email..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="max-w-sm"
                    />
                    <Select value={costCenterFilter} onValueChange={handleCostCenterFilter}>
                        <SelectTrigger className="w-[220px]">
                            <SelectValue placeholder="Filter by cost center" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Cost Centers</SelectItem>
                            <SelectItem value="super">Super Admins</SelectItem>
                            {costCenters.map((center) => (
                                <SelectItem key={center.id} value={center.id}>
                                    {center.center_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Admins Table */}
                <Card>
                    <CardContent className="pt-6">
                        {admins.data.length === 0 ? (
                            <div className="flex items-center justify-center py-12">
                                <div className="text-center">
                                    <p className="text-muted-foreground mb-4">No admin users found</p>
                                    <Button onClick={() => openAdminDialog('create')}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Add First Admin
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Admin</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Access Level</TableHead>
                                        <TableHead>Last Login</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {admins.data.map((admin) => (
                                        <TableRow key={admin.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    {admin.is_super_admin && (
                                                        <Shield className="h-4 w-4 text-red-500" />
                                                    )}
                                                    <div>
                                                        <div className="font-medium">{admin.name}</div>
                                                        {admin.employee && (
                                                            <div className="text-sm text-muted-foreground">
                                                                {admin.employee.emp_system_id}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>{admin.email}</TableCell>
                                            <TableCell>
                                                {admin.is_super_admin ? (
                                                    <Badge variant="destructive">
                                                        Super Admin
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="default">
                                                        {admin.cost_center?.center_name || 'N/A'}
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {admin.last_login_at ? (
                                                    <div className="text-sm">
                                                        <div>
                                                            {new Date(admin.last_login_at).toLocaleString()}
                                                        </div>
                                                        {admin.last_login_ip && (
                                                            <div className="text-muted-foreground">
                                                                {admin.last_login_ip}
                                                            </div>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">Never</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {admin.is_active ? (
                                                    <Badge variant="default">Active</Badge>
                                                ) : (
                                                    <Badge variant="secondary">Inactive</Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            onClick={() => openAdminDialog('edit', admin)}
                                                        >
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            Edit
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => openPasswordDialog(admin)}
                                                        >
                                                            <KeyRound className="mr-2 h-4 w-4" />
                                                            Reset Password
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => handleDelete(admin)}
                                                            className="text-destructive"
                                                            disabled={admin.id === currentUserId}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {admins.links && admins.links.length > 3 && (
                    <div className="flex justify-center gap-2">
                        {admins.links.map((link: PaginationLink, index: number) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            <AdminDialog costCenters={costCenters} />
            <PasswordResetDialog />
        </AppLayout>
    );
}

function AdminDialog({ costCenters }: { costCenters: CostCenter[] }) {
    const { isOpen, mode, admin, close } = useAdminDialog();
    const createMutation = useCreateAdmin();
    const updateMutation = useUpdateAdmin(admin?.id || '');

    const [formData, setFormData] = useState<
        CreateAdminData | (UpdateAdminData & { password?: string; password_confirmation?: string })
    >({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        center_id: undefined,
        is_active: true,
    });

    // Update form data when dialog opens
    useEffect(() => {
        if (mode === 'edit' && admin) {
            setFormData({
                name: admin.name,
                email: admin.email,
                center_id: admin.center_id || undefined,
                employee_id: admin.employee_id || undefined,
                is_active: admin.is_active,
            });
        } else if (mode === 'create') {
            setFormData({
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                center_id: undefined,
                is_active: true,
            });
        }
    }, [mode, admin, isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            const createData = formData as CreateAdminData;
            if (createData.password !== createData.password_confirmation) {
                alert('Passwords do not match');
                return;
            }
            if (createData.password.length < 8) {
                alert('Password must be at least 8 characters');
                return;
            }
            createMutation.mutate(createData, {
                onSuccess: () => {
                    close();
                },
            });
        } else {
            const updateData = formData as UpdateAdminData;
            updateMutation.mutate(updateData, {
                onSuccess: () => {
                    close();
                },
            });
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && close()}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {mode === 'create' ? 'Add Admin User' : 'Edit Admin User'}
                        </DialogTitle>
                        <DialogDescription>
                            {mode === 'create'
                                ? 'Create a new administrative user account with cost center access.'
                                : 'Update admin user details and access permissions.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Full Name *</Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                required
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email Address *</Label>
                            <Input
                                id="email"
                                type="email"
                                value={formData.email}
                                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                required
                            />
                            <p className="text-sm text-muted-foreground">
                                This email will be used for login authentication
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="center_id">Cost Center Access *</Label>
                            <Select
                                value={formData.center_id || 'super'}
                                onValueChange={(value) =>
                                    setFormData({
                                        ...formData,
                                        center_id: value === 'super' ? undefined : value,
                                    })
                                }
                            >
                                <SelectTrigger id="center_id">
                                    <SelectValue placeholder="Select cost center" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="super">
                                        Super Admin (All Cost Centers)
                                    </SelectItem>
                                    {costCenters.map((center) => (
                                        <SelectItem key={center.id} value={center.id}>
                                            {center.center_name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-sm text-yellow-600 dark:text-yellow-500">
                                <strong>Super Admin:</strong> Full system access
                                <br />
                                <strong>Cost Center Admin:</strong> Limited to assigned cost center
                            </p>
                        </div>

                        {mode === 'create' && (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="password">Password *</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={(formData as CreateAdminData).password}
                                        onChange={(e) =>
                                            setFormData({ ...formData, password: e.target.value })
                                        }
                                        required
                                        minLength={8}
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Minimum 8 characters required
                                    </p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">Confirm Password *</Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        value={(formData as CreateAdminData).password_confirmation}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                password_confirmation: e.target.value,
                                            })
                                        }
                                        required
                                        minLength={8}
                                    />
                                </div>
                            </>
                        )}

                        {mode === 'edit' && (
                            <>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={(formData as UpdateAdminData).is_active}
                                        onCheckedChange={(checked) =>
                                            setFormData({
                                                ...formData,
                                                is_active: checked as boolean,
                                            })
                                        }
                                    />
                                    <Label htmlFor="is_active" className="text-sm font-normal">
                                        Active
                                    </Label>
                                </div>

                                <div className="bg-muted rounded-md p-3">
                                    <p className="text-sm text-muted-foreground">
                                        <strong>Note:</strong> To change the password, use the "Reset
                                        Password" option from the actions menu.
                                    </p>
                                </div>
                            </>
                        )}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit">
                            {mode === 'create' ? 'Create Admin' : 'Update Admin'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function PasswordResetDialog() {
    const { isOpen, admin, close } = usePasswordDialog();
    const resetMutation = useResetAdminPassword(admin?.id || '');

    const [passwords, setPasswords] = useState<ResetPasswordData>({
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        if (isOpen) {
            setPasswords({
                password: '',
                password_confirmation: '',
            });
        }
    }, [isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (passwords.password !== passwords.password_confirmation) {
            alert('Passwords do not match');
            return;
        }

        if (passwords.password.length < 8) {
            alert('Password must be at least 8 characters');
            return;
        }

        resetMutation.mutate(passwords, {
            onSuccess: () => {
                close();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && close()}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Reset Admin Password</DialogTitle>
                        <DialogDescription>
                            Reset the password for {admin?.name}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="new_password">New Password *</Label>
                            <Input
                                id="new_password"
                                type="password"
                                value={passwords.password}
                                onChange={(e) =>
                                    setPasswords({ ...passwords, password: e.target.value })
                                }
                                required
                                minLength={8}
                            />
                            <p className="text-sm text-muted-foreground">
                                Minimum 8 characters required
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="confirm_password">Confirm New Password *</Label>
                            <Input
                                id="confirm_password"
                                type="password"
                                value={passwords.password_confirmation}
                                onChange={(e) =>
                                    setPasswords({
                                        ...passwords,
                                        password_confirmation: e.target.value,
                                    })
                                }
                                required
                                minLength={8}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit">Reset Password</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
