import { Badge } from '@/components/ui/badge';
import { useDialog } from '@/hooks/use-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import Heading from '@/components/heading';
import type { CustomTransaction } from '@/hooks/queries/use-custom-transactions';
import {
    useCreateCustomTransaction,
    useDeleteCustomTransaction,
} from '@/hooks/queries/use-custom-transactions';
import { useCustomTransactionStore } from '@/stores/custom-transaction-store';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Plus, Trash2, Users, FileText } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
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
import { useState } from 'react';

interface Props {
    transactions: {
        data: CustomTransaction[];
        links: any[];
        meta: any;
    };
    payrolls: any[];
    periods: any[];
    employees: any[];
    transactionCodes: any[];
    currentPayrollId: string;
    currentPeriodId: number;
    userCenterId: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Custom Transactions', href: '/custom-transactions' },
];

export default function CustomTransactionsIndex({
    transactions,
    payrolls,
    periods,
    employees,
    transactionCodes,
    currentPayrollId,
    currentPeriodId,
}: Props) {
    const { form, setForm, showCreateDialog, setShowCreateDialog } = useCustomTransactionStore();
    const createTransaction = useCreateCustomTransaction();
    const deleteTransaction = useDeleteCustomTransaction();
    const dialog = useDialog();

    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [transactionToDelete, setTransactionToDelete] = useState<number | null>(null);
    const [selectedEmployees, setSelectedEmployees] = useState<string[]>([]);
    const [selectedCodes, setSelectedCodes] = useState<number[]>([]);

    const handlePayrollChange = (payrollId: string) => {
        router.get(
            '/custom-transactions',
            { payroll_id: payrollId, period_id: currentPeriodId },
            { preserveState: false }
        );
    };

    const handlePeriodChange = (periodId: string) => {
        router.get(
            '/custom-transactions',
            { payroll_id: currentPayrollId, period_id: parseInt(periodId) },
            { preserveState: false }
        );
    };

    const handleCreate = () => {
        if (!currentPeriodId) {
            dialog.alert('Please select a period', 'Validation Error');
            return;
        }

        createTransaction.mutate(
            {
                ...form,
                period_id: currentPeriodId,
                employees: selectedEmployees.length > 0 ? selectedEmployees : ['all'],
                transaction_codes: selectedCodes,
            },
            {
                onSuccess: () => {
                    setShowCreateDialog(false);
                    setSelectedEmployees([]);
                    setSelectedCodes([]);
                },
            }
        );
    };

    const handleDelete = () => {
        if (transactionToDelete) {
            deleteTransaction.mutate(transactionToDelete, {
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setTransactionToDelete(null);
                },
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Custom Transactions" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <Heading>Custom Transactions</Heading>
                        <p className="text-muted-foreground mt-1">
                            Manage employee-specific transactions with prorated calculations
                        </p>
                    </div>
                    <Button onClick={() => setShowCreateDialog(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Custom Transaction
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                        <CardDescription>Select payroll and period to view transactions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Payroll</Label>
                                <Select value={currentPayrollId} onValueChange={handlePayrollChange}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select payroll" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {payrolls.map((payroll) => (
                                            <SelectItem key={payroll.id} value={payroll.id}>
                                                {payroll.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Period</Label>
                                <Select
                                    value={currentPeriodId?.toString()}
                                    onValueChange={handlePeriodChange}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select period" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {periods.map((period) => (
                                            <SelectItem
                                                key={period.period_id}
                                                value={period.period_id.toString()}
                                            >
                                                {period.month_name} {period.period_year}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Transactions Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Custom Transactions</CardTitle>
                        <CardDescription>
                            Employee-specific transactions with prorated calculations
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {transactions.data.length === 0 ? (
                            <div className="text-center py-12 text-muted-foreground">
                                No custom transactions found for this period
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Hours</TableHead>
                                        <TableHead>Amount Type</TableHead>
                                        <TableHead>Employees</TableHead>
                                        <TableHead>Codes</TableHead>
                                        <TableHead>Work Ratio</TableHead>
                                        <TableHead>Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactions.data.map((transaction, index) => (
                                        <TableRow key={transaction.custom_id}>
                                            <TableCell>{index + 1}</TableCell>
                                            <TableCell>
                                                {transaction.worked_hours} / {transaction.base_hours}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{transaction.amount_type}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Users className="h-4 w-4" />
                                                    <span>{transaction.employee_count}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <FileText className="h-4 w-4" />
                                                    <span>{transaction.transaction_count}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        transaction.work_ratio > 100
                                                            ? 'destructive'
                                                            : 'default'
                                                    }
                                                >
                                                    {transaction.work_ratio}%
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => {
                                                        setTransactionToDelete(transaction.custom_id);
                                                        setDeleteDialogOpen(true);
                                                    }}
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}

                        {/* Pagination */}
                        {transactions.meta && transactions.meta.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Showing {transactions.meta.from} to {transactions.meta.to} of{' '}
                                    {transactions.meta.total} transactions
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Create Dialog */}
            <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Add Custom Transaction</DialogTitle>
                        <DialogDescription>
                            Create a custom transaction with prorated calculations for selected employees
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Worked Hours</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={form.worked_hours}
                                    onChange={(e) =>
                                        setForm({ worked_hours: parseFloat(e.target.value) || 0 })
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Base Hours</Label>
                                <Input
                                    type="number"
                                    min="1"
                                    step="0.01"
                                    value={form.base_hours}
                                    onChange={(e) =>
                                        setForm({ base_hours: parseFloat(e.target.value) || 176 })
                                    }
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Amount Type</Label>
                            <Select
                                value={form.use_basic ? 'true' : 'false'}
                                onValueChange={(value) =>
                                    setForm({ use_basic: value === 'true' })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="true">Use Basic Salary</SelectItem>
                                    <SelectItem value="false">Use Custom Amount</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {!form.use_basic && (
                            <div className="space-y-2">
                                <Label>Base Amount (USD)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={form.base_amount}
                                    onChange={(e) =>
                                        setForm({ base_amount: parseFloat(e.target.value) || 0 })
                                    }
                                />
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label>Employees</Label>
                            <div className="max-h-48 overflow-y-auto border rounded-md p-2">
                                <div className="space-y-2">
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={selectedEmployees.length === 0}
                                            onChange={(e) => {
                                                if (e.target.checked) {
                                                    setSelectedEmployees([]);
                                                }
                                            }}
                                        />
                                        <span className="text-sm font-medium">All Employees</span>
                                    </label>
                                    {employees.map((emp) => (
                                        <label key={emp.id} className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={selectedEmployees.includes(emp.id)}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        setSelectedEmployees([...selectedEmployees, emp.id]);
                                                    } else {
                                                        setSelectedEmployees(
                                                            selectedEmployees.filter((id) => id !== emp.id)
                                                        );
                                                    }
                                                }}
                                            />
                                            <span className="text-sm">
                                                {emp.firstname} {emp.surname}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Transaction Codes</Label>
                            <div className="max-h-48 overflow-y-auto border rounded-md p-2">
                                <div className="space-y-2">
                                    {transactionCodes.map((code) => (
                                        <label key={code.code_id} className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={selectedCodes.includes(code.code_id)}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        setSelectedCodes([...selectedCodes, code.code_id]);
                                                    } else {
                                                        setSelectedCodes(
                                                            selectedCodes.filter((id) => id !== code.code_id)
                                                        );
                                                    }
                                                }}
                                            />
                                            <span className="text-sm">
                                                {code.code_number} - {code.code_name}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowCreateDialog(false)}>
                            Cancel
                        </Button>
                        <Button
                            onClick={handleCreate}
                            disabled={createTransaction.isPending || selectedCodes.length === 0}
                        >
                            {createTransaction.isPending ? 'Creating...' : 'Create Transaction'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Transaction</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete this custom transaction? This action cannot
                            be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete}>Delete</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
