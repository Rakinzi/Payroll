import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { create } from 'zustand';
import { useDialog } from '@/hooks/use-dialog';
import AppLayout from '@/layouts/app-layout';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
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
import { Textarea } from '@/components/ui/textarea';
import { Calendar, DollarSign, Info, MoreHorizontal, Plus, Users } from 'lucide-react';
import {
    Payroll,
    PayrollFormData,
    useCreatePayroll,
    useUpdatePayroll,
    useDeletePayroll,
    useTogglePayrollStatus,
    useAssignEmployees,
    useRemoveEmployee,
} from '@/hooks/queries/use-payrolls';

interface Employee {
    id: string;
    firstname: string;
    surname: string;
    othername?: string;
    emp_system_id: string;
    position?: {
        position_name: string;
    };
    department?: {
        department_name: string;
    };
}

interface PayrollsPageProps {
    payrolls: {
        data: (Payroll & { employees?: Employee[] })[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    employees: Employee[];
    supportedTypes: string[];
    supportedPeriods: { value: number; label: string }[];
    supportedTaxMethods: string[];
    supportedCurrencies: string[];
}

interface DialogState {
    payrollFormOpen: boolean;
    payrollDeleteOpen: boolean;
    employeeAssignOpen: boolean;
    selectedPayroll: Payroll | null;
    setPayrollFormOpen: (open: boolean) => void;
    setPayrollDeleteOpen: (open: boolean) => void;
    setEmployeeAssignOpen: (open: boolean) => void;
    setSelectedPayroll: (payroll: Payroll | null) => void;
}

const useDialogStore = create<DialogState>((set) => ({
    payrollFormOpen: false,
    payrollDeleteOpen: false,
    employeeAssignOpen: false,
    selectedPayroll: null,
    setPayrollFormOpen: (open) => set({ payrollFormOpen: open }),
    setPayrollDeleteOpen: (open) => set({ payrollDeleteOpen: open }),
    setEmployeeAssignOpen: (open) => set({ employeeAssignOpen: open }),
    setSelectedPayroll: (payroll) => set({ selectedPayroll: payroll }),
}));

function PayrollFormDialog() {
    const { payrollFormOpen, setPayrollFormOpen, selectedPayroll, setSelectedPayroll } =
        useDialogStore();
    const { supportedTypes, supportedPeriods, supportedTaxMethods, supportedCurrencies } =
        usePage<PayrollsPageProps>().props;

    const [payrollName, setPayrollName] = useState(selectedPayroll?.payroll_name || '');
    const [payrollType, setPayrollType] = useState<'Period' | 'Daily' | 'Hourly'>(
        selectedPayroll?.payroll_type || 'Period'
    );
    const [payrollPeriod, setPayrollPeriod] = useState<12 | 26 | 52>(
        selectedPayroll?.payroll_period || 12
    );
    const [startDate, setStartDate] = useState(
        selectedPayroll?.start_date || new Date().toISOString().split('T')[0]
    );
    const [taxMethod, setTaxMethod] = useState(selectedPayroll?.tax_method || 'FDS Average');
    const [payrollCurrency, setPayrollCurrency] = useState(
        selectedPayroll?.payroll_currency || 'USD + ZWG'
    );
    const [description, setDescription] = useState(selectedPayroll?.description || '');
    const [isActive, setIsActive] = useState(selectedPayroll?.is_active ?? true);

    const createMutation = useCreatePayroll();
    const updateMutation = useUpdatePayroll(selectedPayroll?.id || '');

    // Update form when selected payroll changes
    if (selectedPayroll && !payrollName && payrollFormOpen) {
        setPayrollName(selectedPayroll.payroll_name);
        setPayrollType(selectedPayroll.payroll_type);
        setPayrollPeriod(selectedPayroll.payroll_period);
        setStartDate(selectedPayroll.start_date);
        setTaxMethod(selectedPayroll.tax_method);
        setPayrollCurrency(selectedPayroll.payroll_currency);
        setDescription(selectedPayroll.description || '');
        setIsActive(selectedPayroll.is_active);
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        const data: PayrollFormData = {
            payroll_name: payrollName,
            payroll_type: payrollType,
            payroll_period: payrollPeriod,
            start_date: startDate,
            tax_method: taxMethod,
            payroll_currency: payrollCurrency,
            description: description || undefined,
            is_active: isActive,
        };

        try {
            if (selectedPayroll) {
                await updateMutation.mutateAsync(data);
            } else {
                await createMutation.mutateAsync(data);
            }
            handleClose();
        } catch (error) {
            console.error('Failed to save payroll:', error);
        }
    };

    const handleClose = () => {
        setPayrollFormOpen(false);
        setSelectedPayroll(null);
        setPayrollName('');
        setPayrollType('Period');
        setPayrollPeriod(12);
        setStartDate(new Date().toISOString().split('T')[0]);
        setTaxMethod('FDS Average');
        setPayrollCurrency('USD + ZWG');
        setDescription('');
        setIsActive(true);
    };

    const isLoading = createMutation.isPending || updateMutation.isPending;

    return (
        <Dialog open={payrollFormOpen} onOpenChange={setPayrollFormOpen}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>
                        {selectedPayroll ? 'Edit Payroll' : 'Create New Payroll'}
                    </DialogTitle>
                    <DialogDescription>
                        {selectedPayroll
                            ? 'Update the payroll configuration.'
                            : 'Create a new payroll to organize employee payments.'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4 max-h-[60vh] overflow-y-auto px-1">
                        <div className="space-y-2">
                            <Label htmlFor="payroll_name">Payroll Name *</Label>
                            <Input
                                id="payroll_name"
                                value={payrollName}
                                onChange={(e) => setPayrollName(e.target.value)}
                                placeholder="e.g., Monthly Payroll"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="payroll_type">Payroll Type *</Label>
                                <Select
                                    value={payrollType}
                                    onValueChange={(value) =>
                                        setPayrollType(value as 'Period' | 'Daily' | 'Hourly')
                                    }
                                >
                                    <SelectTrigger id="payroll_type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {supportedTypes.map((type) => (
                                            <SelectItem key={type} value={type}>
                                                {type}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="payroll_period">Payroll Period *</Label>
                                <Select
                                    value={payrollPeriod.toString()}
                                    onValueChange={(value) =>
                                        setPayrollPeriod(parseInt(value) as 12 | 26 | 52)
                                    }
                                >
                                    <SelectTrigger id="payroll_period">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {supportedPeriods.map((period) => (
                                            <SelectItem
                                                key={period.value}
                                                value={period.value.toString()}
                                            >
                                                {period.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="start_date">Start Date *</Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="tax_method">Tax Method *</Label>
                            <Select value={taxMethod} onValueChange={setTaxMethod}>
                                <SelectTrigger id="tax_method">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {supportedTaxMethods.map((method) => (
                                        <SelectItem key={method} value={method}>
                                            {method}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="payroll_currency">Payroll Currency *</Label>
                            <Select value={payrollCurrency} onValueChange={setPayrollCurrency}>
                                <SelectTrigger id="payroll_currency">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {supportedCurrencies.map((currency) => (
                                        <SelectItem key={currency} value={currency}>
                                            {currency}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Optional description for this payroll"
                                rows={3}
                                maxLength={1000}
                            />
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                checked={isActive}
                                onCheckedChange={(checked) => setIsActive(checked as boolean)}
                            />
                            <Label htmlFor="is_active" className="cursor-pointer">
                                Active
                            </Label>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading
                                ? 'Saving...'
                                : selectedPayroll
                                  ? 'Update Payroll'
                                  : 'Create Payroll'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeletePayrollDialog() {
    const { payrollDeleteOpen, setPayrollDeleteOpen, selectedPayroll, setSelectedPayroll } =
        useDialogStore();
    const deleteMutation = useDeletePayroll();

    const handleDelete = async () => {
        if (!selectedPayroll) return;

        try {
            await deleteMutation.mutateAsync(selectedPayroll.id);
            setPayrollDeleteOpen(false);
            setSelectedPayroll(null);
        } catch (error) {
            console.error('Failed to delete payroll:', error);
        }
    };

    return (
        <AlertDialog open={payrollDeleteOpen} onOpenChange={setPayrollDeleteOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the payroll{' '}
                        <strong>{selectedPayroll?.payroll_name}</strong> and remove all employee
                        assignments.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleDelete}
                        disabled={deleteMutation.isPending}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {deleteMutation.isPending ? 'Deleting...' : 'Delete'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

function AssignEmployeesDialog() {
    const { employeeAssignOpen, setEmployeeAssignOpen, selectedPayroll } = useDialogStore();
    const { employees } = usePage<PayrollsPageProps>().props;
    const [selectedEmployeeIds, setSelectedEmployeeIds] = useState<string[]>([]);

    const assignMutation = useAssignEmployees(selectedPayroll?.id || '');
    const dialog = useDialog();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (selectedEmployeeIds.length === 0) {
            dialog.alert('Please select at least one employee', 'Validation Error');
            return;
        }

        try {
            await assignMutation.mutateAsync({ employee_ids: selectedEmployeeIds });
            handleClose();
        } catch (error) {
            console.error('Failed to assign employees:', error);
        }
    };

    const handleClose = () => {
        setEmployeeAssignOpen(false);
        setSelectedEmployeeIds([]);
    };

    const toggleEmployee = (employeeId: string) => {
        setSelectedEmployeeIds((prev) =>
            prev.includes(employeeId)
                ? prev.filter((id) => id !== employeeId)
                : [...prev, employeeId]
        );
    };

    return (
        <Dialog open={employeeAssignOpen} onOpenChange={setEmployeeAssignOpen}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Assign Employees to {selectedPayroll?.payroll_name}</DialogTitle>
                    <DialogDescription>
                        Select employees to assign to this payroll. Employees will be automatically
                        removed from their previous payroll assignments.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="max-h-[400px] overflow-y-auto space-y-2 border rounded-lg p-4">
                            {employees.map((employee) => (
                                <div
                                    key={employee.id}
                                    className="flex items-start space-x-2 p-2 hover:bg-muted rounded"
                                >
                                    <Checkbox
                                        id={employee.id}
                                        checked={selectedEmployeeIds.includes(employee.id)}
                                        onCheckedChange={() => toggleEmployee(employee.id)}
                                    />
                                    <div className="flex-1">
                                        <Label
                                            htmlFor={employee.id}
                                            className="cursor-pointer font-normal"
                                        >
                                            <div className="font-medium">
                                                {employee.firstname}{' '}
                                                {employee.othername && `${employee.othername} `}
                                                {employee.surname}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {employee.emp_system_id}
                                                {employee.position &&
                                                    ` • ${employee.position.position_name}`}
                                                {employee.department &&
                                                    ` • ${employee.department.department_name}`}
                                            </div>
                                        </Label>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {selectedEmployeeIds.length > 0 && (
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    {selectedEmployeeIds.length} employee(s) selected for
                                    assignment
                                </AlertDescription>
                            </Alert>
                        )}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={assignMutation.isPending || selectedEmployeeIds.length === 0}
                        >
                            {assignMutation.isPending
                                ? 'Assigning...'
                                : `Assign ${selectedEmployeeIds.length} Employee(s)`}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function PayrollsIndex() {
    const { payrolls } = usePage<PayrollsPageProps>().props;
    const {
        setPayrollFormOpen,
        setPayrollDeleteOpen,
        setEmployeeAssignOpen,
        setSelectedPayroll,
    } = useDialogStore();

    const toggleStatusMutation = useTogglePayrollStatus();

    const handleAddPayroll = () => {
        setSelectedPayroll(null);
        setPayrollFormOpen(true);
    };

    const handleEditPayroll = (payroll: Payroll) => {
        setSelectedPayroll(payroll);
        setPayrollFormOpen(true);
    };

    const handleDeletePayroll = (payroll: Payroll) => {
        setSelectedPayroll(payroll);
        setPayrollDeleteOpen(true);
    };

    const handleAssignEmployees = (payroll: Payroll) => {
        setSelectedPayroll(payroll);
        setEmployeeAssignOpen(true);
    };

    const handleToggleStatus = async (payrollId: string) => {
        try {
            await toggleStatusMutation.mutateAsync(payrollId);
        } catch (error) {
            console.error('Failed to toggle payroll status:', error);
        }
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Payroll Management
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Create and manage payrolls for employee compensation
                    </p>
                </div>
            }
        >
            <Head title="Payrolls" />

            <div className="space-y-6">
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Payroll System:</strong> Organize employees into payrolls for
                        periodic payment processing. Each employee can only be assigned to one
                        active payroll at a time.
                    </AlertDescription>
                </Alert>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <DollarSign className="h-5 w-5" />
                                    Payrolls
                                </CardTitle>
                                <CardDescription>
                                    Manage payroll configurations and employee assignments
                                </CardDescription>
                            </div>
                            <Button onClick={handleAddPayroll}>
                                <Plus className="mr-2 h-4 w-4" />
                                Create Payroll
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Payroll Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Period</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead>Start Date</TableHead>
                                    <TableHead>Employees</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {payrolls.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="text-center text-muted-foreground"
                                        >
                                            No payrolls created yet. Create your first payroll to
                                            get started.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    payrolls.data.map((payroll) => (
                                        <TableRow key={payroll.id}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {payroll.payroll_name}
                                                </div>
                                                {payroll.description && (
                                                    <div className="text-xs text-muted-foreground mt-1">
                                                        {payroll.description}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {payroll.payroll_type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm">
                                                    {payroll.period_type}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {payroll.currency_display}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-4 w-4 text-muted-foreground" />
                                                    {new Date(
                                                        payroll.start_date
                                                    ).toLocaleDateString()}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleAssignEmployees(payroll)}
                                                    className="h-8"
                                                >
                                                    <Users className="h-4 w-4 mr-2" />
                                                    {payroll.active_employee_count}
                                                </Button>
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleToggleStatus(payroll.id)}
                                                    disabled={toggleStatusMutation.isPending}
                                                >
                                                    <Badge
                                                        variant={
                                                            payroll.is_active
                                                                ? 'default'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {payroll.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </Button>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                            <span className="sr-only">
                                                                Actions
                                                            </span>
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuLabel>
                                                            Actions
                                                        </DropdownMenuLabel>
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                handleEditPayroll(payroll)
                                                            }
                                                        >
                                                            Edit
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                handleAssignEmployees(payroll)
                                                            }
                                                        >
                                                            Manage Employees
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                handleDeletePayroll(payroll)
                                                            }
                                                            className="text-destructive"
                                                        >
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <PayrollFormDialog />
            <DeletePayrollDialog />
            <AssignEmployeesDialog />
        </AppLayout>
    );
}
