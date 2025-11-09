import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import Heading from '@/components/heading';
import type { DefaultTransaction } from '@/hooks/queries/use-default-transactions';
import {
    useSaveDefaultTransactions,
    useDeleteDefaultTransaction,
    useClearAllDefaultTransactions,
} from '@/hooks/queries/use-default-transactions';
import { useDefaultTransactionStore } from '@/stores/default-transaction-store';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Plus, Save, Trash2, AlertCircle } from 'lucide-react';
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
    currentPeriod: any | null;
    transactions: DefaultTransaction[];
    transactionCodes: Array<{
        code_id: number;
        code_number: string;
        code_name: string;
    }>;
    userCenterId: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Default Transactions', href: '/default-transactions' },
];

export default function DefaultTransactionsIndex({
    currentPeriod,
    transactions,
    transactionCodes,
    userCenterId,
}: Props) {
    const { rows, addRow, removeRow, updateRow, getValidRows, validateRows } =
        useDefaultTransactionStore();

    const saveTransactions = useSaveDefaultTransactions();
    const deleteTransaction = useDeleteDefaultTransaction();
    const clearAll = useClearAllDefaultTransactions();

    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [clearAllDialogOpen, setClearAllDialogOpen] = useState(false);
    const [transactionToDelete, setTransactionToDelete] = useState<number | null>(null);

    const handleSave = () => {
        if (!currentPeriod) {
            return;
        }

        if (!validateRows()) {
            alert('Please fill in all required fields correctly');
            return;
        }

        const validRows = getValidRows();
        if (validRows.length === 0) {
            alert('Please add at least one transaction');
            return;
        }

        saveTransactions.mutate({
            period_id: currentPeriod.period_id,
            transactions: validRows,
        });
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

    const handleClearAll = () => {
        if (currentPeriod) {
            clearAll.mutate(currentPeriod.period_id, {
                onSuccess: () => {
                    setClearAllDialogOpen(false);
                },
            });
        }
    };

    if (!currentPeriod) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Default Transactions" />
                <div className="space-y-6">
                    <Heading>Default Transactions</Heading>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center py-12">
                                <AlertCircle className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                <p className="text-lg font-medium">No Active Period Found</p>
                                <p className="text-muted-foreground mt-2">
                                    Default transactions can only be managed for the current active period.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Default Transactions" />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <Heading>Default Transactions</Heading>
                    <p className="text-muted-foreground mt-1">
                        Manage default transactions for the current payroll period. These transactions
                        will be applied to ALL employees.
                    </p>
                </div>

                {/* Period Info */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Current Period</CardTitle>
                                <CardDescription>
                                    {currentPeriod.month_name} {currentPeriod.period_year}
                                </CardDescription>
                            </div>
                            <Badge variant="default">Active</Badge>
                        </div>
                    </CardHeader>
                </Card>

                {/* Transaction Form */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Add Default Transactions</CardTitle>
                                <CardDescription>
                                    Add one or more transactions that will apply to all employees
                                </CardDescription>
                            </div>
                            <div className="flex gap-2">
                                <Button onClick={addRow} variant="outline">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Add Row
                                </Button>
                                <Button onClick={handleSave} disabled={saveTransactions.isPending}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {saveTransactions.isPending ? 'Saving...' : 'Save All'}
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {rows.map((row, index) => (
                                <div key={row.id} className="grid grid-cols-12 gap-3 items-end">
                                    <div className="col-span-1">
                                        <Label className="text-xs">#{index + 1}</Label>
                                    </div>
                                    <div className="col-span-3">
                                        {index === 0 && <Label>Transaction Code</Label>}
                                        <Select
                                            value={row.code_id.toString()}
                                            onValueChange={(value) =>
                                                updateRow(row.id, { code_id: parseInt(value) })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select code" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {transactionCodes.map((code) => (
                                                    <SelectItem
                                                        key={code.code_id}
                                                        value={code.code_id.toString()}
                                                    >
                                                        {code.code_number} - {code.code_name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="col-span-2">
                                        {index === 0 && <Label>Effect</Label>}
                                        <Select
                                            value={row.transaction_effect}
                                            onValueChange={(value: '+' | '-') =>
                                                updateRow(row.id, { transaction_effect: value })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="+">Addition (+)</SelectItem>
                                                <SelectItem value="-">Deduction (-)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="col-span-2">
                                        {index === 0 && <Label>Employee Amount</Label>}
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={row.employee_amount}
                                            onChange={(e) =>
                                                updateRow(row.id, {
                                                    employee_amount: parseFloat(e.target.value) || 0,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="col-span-2">
                                        {index === 0 && <Label>Employer Amount</Label>}
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={row.employer_amount}
                                            onChange={(e) =>
                                                updateRow(row.id, {
                                                    employer_amount: parseFloat(e.target.value) || 0,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="col-span-1">
                                        {index === 0 && <Label>Currency</Label>}
                                        <Select
                                            value={row.transaction_currency}
                                            onValueChange={(value: 'ZWL' | 'USD') =>
                                                updateRow(row.id, { transaction_currency: value })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="USD">USD</SelectItem>
                                                <SelectItem value="ZWL">ZWG</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="col-span-1">
                                        {index === 0 && <Label>&nbsp;</Label>}
                                        <Button
                                            variant="destructive"
                                            size="icon"
                                            onClick={() => removeRow(row.id)}
                                            disabled={rows.length === 1}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Existing Transactions Table */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Existing Default Transactions</CardTitle>
                                <CardDescription>
                                    Transactions currently configured for this period
                                </CardDescription>
                            </div>
                            {transactions.length > 0 && (
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={() => setClearAllDialogOpen(true)}
                                >
                                    Clear All
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {transactions.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground">
                                No default transactions configured for this period
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Effect</TableHead>
                                        <TableHead>Employee Amount</TableHead>
                                        <TableHead>Employer Amount</TableHead>
                                        <TableHead>Currency</TableHead>
                                        <TableHead>Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactions.map((transaction, index) => (
                                        <TableRow key={transaction.default_id}>
                                            <TableCell>{index + 1}</TableCell>
                                            <TableCell>
                                                {transaction.transaction_code?.code_number} -{' '}
                                                {transaction.transaction_code?.code_name}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        transaction.transaction_effect === '+'
                                                            ? 'default'
                                                            : 'destructive'
                                                    }
                                                >
                                                    {transaction.effect_display}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {transaction.currency_symbol}
                                                {transaction.employee_amount.toFixed(2)}
                                            </TableCell>
                                            <TableCell>
                                                {transaction.currency_symbol}
                                                {transaction.employer_amount.toFixed(2)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {transaction.transaction_currency}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => {
                                                        setTransactionToDelete(transaction.default_id);
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
                    </CardContent>
                </Card>

                {/* Information Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>About Default Transactions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            <strong>Default transactions</strong> are applied to all employees during
                            period processing. Use them for organization-wide deductions or allowances.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            <strong>Employee Amount:</strong> Amount deducted from or added to employee's
                            salary
                        </p>
                        <p className="text-sm text-muted-foreground">
                            <strong>Employer Amount:</strong> Amount contributed by employer (e.g., pension
                            contributions)
                        </p>
                        <p className="text-sm text-muted-foreground">
                            <strong>Currency:</strong> Select USD or ZWG for each transaction
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Delete Confirmation Dialog */}
            <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Transaction</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete this default transaction? This action cannot
                            be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete}>Delete</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Clear All Confirmation Dialog */}
            <AlertDialog open={clearAllDialogOpen} onOpenChange={setClearAllDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Clear All Transactions</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to clear all default transactions for this period?
                            This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleClearAll}>Clear All</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
