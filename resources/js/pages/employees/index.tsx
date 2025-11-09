import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    type Employee,
    useDeleteEmployee,
    useRestoreEmployee,
    useTerminateEmployee,
} from '@/hooks/queries/use-employees';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Trash2, UserCheck, UserMinus } from 'lucide-react';
import { useState } from 'react';
import { create } from 'zustand';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Employees', href: '/employees' },
];

interface TerminateDialogStore {
    isOpen: boolean;
    employee: Employee | null;
    open: (employee: Employee) => void;
    close: () => void;
}

const useTerminateDialog = create<TerminateDialogStore>((set) => ({
    isOpen: false,
    employee: null,
    open: (employee) => set({ isOpen: true, employee }),
    close: () => set({ isOpen: false, employee: null }),
}));

interface Props {
    employees: {
        data: Employee[];
        links: any[];
        meta: any;
    };
    filters?: {
        search?: string;
        status?: string;
        department_id?: string;
    };
}

export default function EmployeeListPage({ employees, filters }: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const deleteMutation = useDeleteEmployee();
    const restoreMutation = useRestoreEmployee();
    const { open: openTerminateDialog } = useTerminateDialog();

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get('/employees', { search: value }, { preserveState: true, replace: true });
    };

    const handleDelete = (employee: Employee) => {
        if (confirm(`Are you sure you want to delete ${employee.firstname} ${employee.surname}?`)) {
            deleteMutation.mutate(employee.id);
        }
    };

    const handleRestore = (employee: Employee) => {
        if (confirm(`Are you sure you want to restore ${employee.firstname} ${employee.surname}?`)) {
            restoreMutation.mutate(employee.id);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Employees" />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Employees</h1>
                        <p className="text-muted-foreground">Manage your workforce and employee records</p>
                    </div>
                    <Link href="/employees/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Add Employee
                        </Button>
                    </Link>
                </div>

                {/* Search & Filters */}
                <div className="flex gap-4">
                    <Input
                        placeholder="Search by name, ID, email..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="max-w-sm"
                    />
                </div>

                {/* Employee Table */}
                <Card>
                    <CardContent className="pt-6">
                        {employees.data.length === 0 ? (
                            <div className="flex items-center justify-center py-12">
                                <div className="text-center">
                                    <p className="text-muted-foreground mb-4">No employees found</p>
                                    <Link href="/employees/create">
                                        <Button>
                                            <Plus className="mr-2 h-4 w-4" />
                                            Add First Employee
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Employee ID</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Department</TableHead>
                                        <TableHead>Position</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {employees.data.map((employee) => (
                                        <TableRow key={employee.id}>
                                            <TableCell className="font-medium">
                                                {employee.emp_system_id}
                                            </TableCell>
                                            <TableCell>
                                                {employee.firstname} {employee.surname}
                                            </TableCell>
                                            <TableCell>{employee.emp_email}</TableCell>
                                            <TableCell>
                                                {employee.department?.dept_name || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {employee.position?.position_name || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {employee.is_ex ? (
                                                    <Badge variant="destructive">Terminated</Badge>
                                                ) : employee.is_active ? (
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
                                                        <DropdownMenuItem asChild>
                                                            <Link href={`/employees/${employee.id}/edit`}>
                                                                <Pencil className="mr-2 h-4 w-4" />
                                                                Edit
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        {employee.is_ex ? (
                                                            <DropdownMenuItem
                                                                onClick={() => handleRestore(employee)}
                                                            >
                                                                <UserCheck className="mr-2 h-4 w-4" />
                                                                Restore
                                                            </DropdownMenuItem>
                                                        ) : (
                                                            <DropdownMenuItem
                                                                onClick={() =>
                                                                    openTerminateDialog(employee)
                                                                }
                                                            >
                                                                <UserMinus className="mr-2 h-4 w-4" />
                                                                Terminate
                                                            </DropdownMenuItem>
                                                        )}
                                                        <DropdownMenuItem
                                                            onClick={() => handleDelete(employee)}
                                                            className="text-destructive"
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
                {employees.links && employees.links.length > 3 && (
                    <div className="flex justify-center gap-2">
                        {employees.links.map((link: any, index: number) => (
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

            <TerminateEmployeeDialog />
        </AppLayout>
    );
}

function TerminateEmployeeDialog() {
    const { isOpen, employee, close } = useTerminateDialog();
    const terminateMutation = useTerminateEmployee();
    const [formData, setFormData] = useState({
        is_ex_on: new Date().toISOString().split('T')[0],
        employment_status: 'RESIGNED',
        discharge_notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!employee) return;

        terminateMutation.mutate(
            { employeeId: employee.id, data: formData },
            {
                onSuccess: () => {
                    close();
                    setFormData({
                        is_ex_on: new Date().toISOString().split('T')[0],
                        employment_status: 'RESIGNED',
                        discharge_notes: '',
                    });
                },
            }
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && close()}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Terminate Employee</DialogTitle>
                        <DialogDescription>
                            Terminate {employee?.firstname} {employee?.surname}. This action can be reversed
                            later.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="is_ex_on">Termination Date *</Label>
                            <Input
                                id="is_ex_on"
                                type="date"
                                value={formData.is_ex_on}
                                onChange={(e) =>
                                    setFormData({ ...formData, is_ex_on: e.target.value })
                                }
                                required
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="employment_status">Termination Reason *</Label>
                            <Select
                                value={formData.employment_status}
                                onValueChange={(value) =>
                                    setFormData({ ...formData, employment_status: value })
                                }
                            >
                                <SelectTrigger id="employment_status">
                                    <SelectValue placeholder="Select reason" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="END CONTRACT">End of Contract</SelectItem>
                                    <SelectItem value="RESIGNED">Resigned</SelectItem>
                                    <SelectItem value="DISMISSED">Dismissed</SelectItem>
                                    <SelectItem value="DECEASED">Deceased</SelectItem>
                                    <SelectItem value="SUSPENDED">Suspended</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="discharge_notes">Notes</Label>
                            <Input
                                id="discharge_notes"
                                value={formData.discharge_notes}
                                onChange={(e) =>
                                    setFormData({ ...formData, discharge_notes: e.target.value })
                                }
                                placeholder="Optional discharge notes..."
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="destructive">
                            Terminate Employee
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
