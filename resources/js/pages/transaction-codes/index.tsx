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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    type TransactionCode,
    useDeleteTransactionCode,
    useCreateTransactionCode,
    useUpdateTransactionCode,
    CATEGORY_COLORS,
} from '@/hooks/queries/use-transaction-codes';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Trash2, Shield } from 'lucide-react';
import { useState } from 'react';
import { create } from 'zustand';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Transaction Codes', href: '/transaction-codes' },
];

interface TransactionCodeDialogStore {
    isOpen: boolean;
    transactionCode: TransactionCode | null;
    mode: 'create' | 'edit';
    open: (mode: 'create' | 'edit', transactionCode?: TransactionCode) => void;
    close: () => void;
}

const useTransactionCodeDialog = create<TransactionCodeDialogStore>((set) => ({
    isOpen: false,
    transactionCode: null,
    mode: 'create',
    open: (mode, transactionCode) => set({ isOpen: true, mode, transactionCode: transactionCode || null }),
    close: () => set({ isOpen: false, transactionCode: null }),
}));

interface Props {
    transactionCodes: {
        data: TransactionCode[];
        links: any[];
        meta: any;
    };
    filters?: {
        search?: string;
        category?: string;
        is_active?: string;
    };
    categories: string[];
}

export default function TransactionCodesPage({ transactionCodes, filters, categories }: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [categoryFilter, setCategoryFilter] = useState(filters?.category ?? 'all');
    const deleteMutation = useDeleteTransactionCode();
    const { open: openDialog } = useTransactionCodeDialog();

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get('/transaction-codes', { search: value, category: categoryFilter !== 'all' ? categoryFilter : undefined }, { preserveState: true, replace: true });
    };

    const handleCategoryFilter = (value: string) => {
        setCategoryFilter(value);
        router.get('/transaction-codes', { search, category: value !== 'all' ? value : undefined }, { preserveState: true, replace: true });
    };

    const handleDelete = (transactionCode: TransactionCode) => {
        if (confirm(`Are you sure you want to delete ${transactionCode.code_name}?`)) {
            deleteMutation.mutate(transactionCode.id);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaction Codes" />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Transaction Codes</h1>
                        <p className="text-muted-foreground">
                            Manage earnings, deductions, and contributions for payroll processing
                        </p>
                    </div>
                    <Button onClick={() => openDialog('create')}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Transaction Code
                    </Button>
                </div>

                {/* Search & Filters */}
                <div className="flex gap-4">
                    <Input
                        placeholder="Search by code name or number..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="max-w-sm"
                    />
                    <Select value={categoryFilter} onValueChange={handleCategoryFilter}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Filter by category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Categories</SelectItem>
                            {categories.map((category) => (
                                <SelectItem key={category} value={category}>
                                    {category}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Transaction Codes Table */}
                <Card>
                    <CardContent className="pt-6">
                        {transactionCodes.data.length === 0 ? (
                            <div className="flex items-center justify-center py-12">
                                <div className="text-center">
                                    <p className="text-muted-foreground mb-4">No transaction codes found</p>
                                    <Button onClick={() => openDialog('create')}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Add First Transaction Code
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Category</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Amount/Percentage</TableHead>
                                        <TableHead>Benefit</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactionCodes.data.map((code) => (
                                        <TableRow key={code.id}>
                                            <TableCell className="font-medium">
                                                <div className="flex items-center gap-2">
                                                    {code.formatted_code}
                                                    {!code.is_editable && (
                                                        <Shield className="h-3 w-3 text-muted-foreground" />
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>{code.code_name}</TableCell>
                                            <TableCell>
                                                <Badge variant={CATEGORY_COLORS[code.code_category]}>
                                                    {code.code_category}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {code.code_amount && code.code_amount > 0
                                                    ? 'Fixed Amount'
                                                    : code.code_percentage && code.code_percentage > 0
                                                    ? 'Percentage'
                                                    : 'Threshold-based'}
                                            </TableCell>
                                            <TableCell>
                                                {code.code_amount && code.code_amount > 0
                                                    ? `$${code.code_amount.toFixed(2)}`
                                                    : code.code_percentage && code.code_percentage > 0
                                                    ? `${code.code_percentage.toFixed(2)}%`
                                                    : '-'}
                                            </TableCell>
                                            <TableCell>
                                                {code.is_benefit ? (
                                                    <Badge variant="outline">Benefit</Badge>
                                                ) : (
                                                    '-'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {code.is_active ? (
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
                                                            onClick={() => openDialog('edit', code)}
                                                            disabled={!code.is_editable}
                                                        >
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            Edit
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => handleDelete(code)}
                                                            className="text-destructive"
                                                            disabled={!code.is_editable}
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
                {transactionCodes.links && transactionCodes.links.length > 3 && (
                    <div className="flex justify-center gap-2">
                        {transactionCodes.links.map((link: any, index: number) => (
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

            <TransactionCodeDialog />
        </AppLayout>
    );
}

function TransactionCodeDialog() {
    const { isOpen, mode, transactionCode, close } = useTransactionCodeDialog();
    const createMutation = useCreateTransactionCode();
    const updateMutation = useUpdateTransactionCode(transactionCode?.id || '');
    const [formData, setFormData] = useState({
        code_name: '',
        code_category: 'Earning' as 'Earning' | 'Deduction' | 'Contribution',
        is_benefit: false,
        code_amount: '',
        code_percentage: '',
        minimum_threshold: '',
        maximum_threshold: '',
        description: '',
        is_active: true,
    });

    // Update form data when dialog opens
    useState(() => {
        if (mode === 'edit' && transactionCode) {
            setFormData({
                code_name: transactionCode.code_name,
                code_category: transactionCode.code_category,
                is_benefit: transactionCode.is_benefit,
                code_amount: transactionCode.code_amount?.toString() || '',
                code_percentage: transactionCode.code_percentage?.toString() || '',
                minimum_threshold: transactionCode.minimum_threshold?.toString() || '',
                maximum_threshold: transactionCode.maximum_threshold?.toString() || '',
                description: transactionCode.description || '',
                is_active: transactionCode.is_active,
            });
        } else {
            setFormData({
                code_name: '',
                code_category: 'Earning',
                is_benefit: false,
                code_amount: '',
                code_percentage: '',
                minimum_threshold: '',
                maximum_threshold: '',
                description: '',
                is_active: true,
            });
        }
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const data = {
            code_name: formData.code_name,
            code_category: formData.code_category,
            is_benefit: formData.is_benefit,
            code_amount: formData.code_amount ? parseFloat(formData.code_amount) : undefined,
            code_percentage: formData.code_percentage ? parseFloat(formData.code_percentage) : undefined,
            minimum_threshold: formData.minimum_threshold ? parseFloat(formData.minimum_threshold) : undefined,
            maximum_threshold: formData.maximum_threshold ? parseFloat(formData.maximum_threshold) : undefined,
            description: formData.description,
            is_active: formData.is_active,
        };

        const mutation = mode === 'create' ? createMutation : updateMutation;
        mutation.mutate(data, {
            onSuccess: () => {
                close();
                router.reload();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && close()}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {mode === 'create' ? 'Add Transaction Code' : 'Edit Transaction Code'}
                        </DialogTitle>
                        <DialogDescription>
                            {mode === 'create'
                                ? 'Create a new transaction code for earnings, deductions, or contributions.'
                                : 'Update the transaction code details.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="code_name">Code Name *</Label>
                            <Input
                                id="code_name"
                                value={formData.code_name}
                                onChange={(e) => setFormData({ ...formData, code_name: e.target.value })}
                                required
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="code_category">Category *</Label>
                            <Select
                                value={formData.code_category}
                                onValueChange={(value: 'Earning' | 'Deduction' | 'Contribution') => {
                                    setFormData({
                                        ...formData,
                                        code_category: value,
                                        // Reset is_benefit if not Earning
                                        is_benefit: value === 'Earning' ? formData.is_benefit : false,
                                    });
                                }}
                            >
                                <SelectTrigger id="code_category">
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Earning">Earning</SelectItem>
                                    <SelectItem value="Deduction">Deduction</SelectItem>
                                    <SelectItem value="Contribution">Contribution</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {formData.code_category === 'Earning' && (
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_benefit"
                                    checked={formData.is_benefit}
                                    onCheckedChange={(checked) =>
                                        setFormData({ ...formData, is_benefit: checked as boolean })
                                    }
                                />
                                <Label htmlFor="is_benefit" className="text-sm font-normal">
                                    Mark as Employee Benefit (Non-taxable)
                                </Label>
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="code_amount">Fixed Amount</Label>
                                <Input
                                    id="code_amount"
                                    type="number"
                                    step="0.01"
                                    value={formData.code_amount}
                                    onChange={(e) =>
                                        setFormData({ ...formData, code_amount: e.target.value })
                                    }
                                    placeholder="0.00"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="code_percentage">Percentage (%)</Label>
                                <Input
                                    id="code_percentage"
                                    type="number"
                                    step="0.01"
                                    value={formData.code_percentage}
                                    onChange={(e) =>
                                        setFormData({ ...formData, code_percentage: e.target.value })
                                    }
                                    placeholder="0.00"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="minimum_threshold">Minimum Threshold</Label>
                                <Input
                                    id="minimum_threshold"
                                    type="number"
                                    step="0.01"
                                    value={formData.minimum_threshold}
                                    onChange={(e) =>
                                        setFormData({ ...formData, minimum_threshold: e.target.value })
                                    }
                                    placeholder="0.00"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="maximum_threshold">Maximum Threshold</Label>
                                <Input
                                    id="maximum_threshold"
                                    type="number"
                                    step="0.01"
                                    value={formData.maximum_threshold}
                                    onChange={(e) =>
                                        setFormData({ ...formData, maximum_threshold: e.target.value })
                                    }
                                    placeholder="0.00"
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Input
                                id="description"
                                value={formData.description}
                                onChange={(e) =>
                                    setFormData({ ...formData, description: e.target.value })
                                }
                                placeholder="Optional description..."
                            />
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                checked={formData.is_active}
                                onCheckedChange={(checked) =>
                                    setFormData({ ...formData, is_active: checked as boolean })
                                }
                            />
                            <Label htmlFor="is_active" className="text-sm font-normal">
                                Active
                            </Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit">
                            {mode === 'create' ? 'Create Transaction Code' : 'Update Transaction Code'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
