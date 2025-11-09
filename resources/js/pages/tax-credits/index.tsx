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
import { Textarea } from '@/components/ui/textarea';
import {
    type TaxCredit,
    useDeleteTaxCredit,
    useCreateTaxCredit,
    useUpdateTaxCredit,
    CURRENCY_COLORS,
    PERIOD_COLORS,
} from '@/hooks/queries/use-tax-credits';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type PaginatedData, type PaginationLink } from '@/types';
import { Head, router } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Trash2, Lock, Info } from 'lucide-react';
import { useState, useEffect } from 'react';
import { create } from 'zustand';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Tax Credits', href: '/tax-credits' },
];

interface TaxCreditDialogStore {
    isOpen: boolean;
    taxCredit: TaxCredit | null;
    mode: 'create' | 'edit';
    open: (mode: 'create' | 'edit', taxCredit?: TaxCredit) => void;
    close: () => void;
}

const useTaxCreditDialog = create<TaxCreditDialogStore>((set) => ({
    isOpen: false,
    taxCredit: null,
    mode: 'create',
    open: (mode, taxCredit) => set({ isOpen: true, mode, taxCredit: taxCredit || null }),
    close: () => set({ isOpen: false, taxCredit: null }),
}));

interface Props {
    taxCredits: PaginatedData<TaxCredit>;
    filters?: {
        search?: string;
        currency?: string;
        period?: string;
    };
    currencies: string[];
    periods: string[];
}

export default function TaxCreditsPage({ taxCredits, filters, currencies, periods }: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [currencyFilter, setCurrencyFilter] = useState(filters?.currency ?? 'all');
    const [periodFilter, setPeriodFilter] = useState(filters?.period ?? 'all');
    const deleteMutation = useDeleteTaxCredit();
    const { open: openDialog } = useTaxCreditDialog();

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(
            '/tax-credits',
            {
                search: value,
                currency: currencyFilter !== 'all' ? currencyFilter : undefined,
                period: periodFilter !== 'all' ? periodFilter : undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const handleCurrencyFilter = (value: string) => {
        setCurrencyFilter(value);
        router.get(
            '/tax-credits',
            {
                search,
                currency: value !== 'all' ? value : undefined,
                period: periodFilter !== 'all' ? periodFilter : undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const handlePeriodFilter = (value: string) => {
        setPeriodFilter(value);
        router.get(
            '/tax-credits',
            {
                search,
                currency: currencyFilter !== 'all' ? currencyFilter : undefined,
                period: value !== 'all' ? value : undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const handleDelete = (taxCredit: TaxCredit) => {
        if (confirm(`Are you sure you want to delete ${taxCredit.credit_name}?`)) {
            deleteMutation.mutate(taxCredit.id);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tax Credits" />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Tax Credits</h1>
                        <p className="text-muted-foreground">
                            Manage tax credits applied during payroll calculations
                        </p>
                    </div>
                    <Button onClick={() => openDialog('create')}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Tax Credit
                    </Button>
                </div>

                {/* Search & Filters */}
                <div className="flex gap-4">
                    <Input
                        placeholder="Search by credit name..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="max-w-sm"
                    />
                    <Select value={currencyFilter} onValueChange={handleCurrencyFilter}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Filter by currency" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Currencies</SelectItem>
                            {currencies.map((currency) => (
                                <SelectItem key={currency} value={currency}>
                                    {currency}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={periodFilter} onValueChange={handlePeriodFilter}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Filter by period" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Periods</SelectItem>
                            {periods.map((period) => (
                                <SelectItem key={period} value={period}>
                                    {period.charAt(0).toUpperCase() + period.slice(1)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Tax Credits Table */}
                <Card>
                    <CardContent className="pt-6">
                        {taxCredits.data.length === 0 ? (
                            <div className="flex items-center justify-center py-12">
                                <div className="text-center">
                                    <p className="text-muted-foreground mb-4">No tax credits found</p>
                                    <Button onClick={() => openDialog('create')}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Add First Tax Credit
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Credit Name</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Currency</TableHead>
                                        <TableHead>Period</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {taxCredits.data.map((credit) => (
                                        <TableRow key={credit.id}>
                                            <TableCell className="font-medium">
                                                <code className="bg-muted px-2 py-1 rounded text-sm">
                                                    {credit.credit_name}
                                                </code>
                                            </TableCell>
                                            <TableCell className="font-semibold">
                                                {credit.formatted_value}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={CURRENCY_COLORS[credit.currency]}>
                                                    {credit.currency}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={PERIOD_COLORS[credit.period]}>
                                                    {credit.period.charAt(0).toUpperCase() +
                                                        credit.period.slice(1)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {credit.description ? (
                                                    <span className="text-sm text-muted-foreground">
                                                        {credit.description.length > 50
                                                            ? `${credit.description.substring(0, 50)}...`
                                                            : credit.description}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground italic">
                                                        No description
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {credit.is_active ? (
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
                                                            onClick={() => openDialog('edit', credit)}
                                                        >
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            Edit
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => handleDelete(credit)}
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
                {taxCredits.links && taxCredits.links.length > 3 && (
                    <div className="flex justify-center gap-2">
                        {taxCredits.links.map((link: PaginationLink, index: number) => (
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

            <TaxCreditDialog />
        </AppLayout>
    );
}

function TaxCreditDialog() {
    const { isOpen, mode, taxCredit, close } = useTaxCreditDialog();
    const createMutation = useCreateTaxCredit();
    const updateMutation = useUpdateTaxCredit(taxCredit?.id || '');
    const [formData, setFormData] = useState({
        credit_name: '',
        credit_amount: '',
        currency: 'USD' as 'USD' | 'ZWG',
        period: 'monthly' as 'monthly' | 'annual',
        description: '',
        is_active: true,
    });

    // Update form data when dialog opens
    useEffect(() => {
        if (mode === 'edit' && taxCredit) {
            setFormData({
                credit_name: taxCredit.credit_name,
                credit_amount: taxCredit.credit_amount.toString(),
                currency: taxCredit.currency,
                period: taxCredit.period,
                description: taxCredit.description || '',
                is_active: taxCredit.is_active,
            });
        } else if (mode === 'create') {
            setFormData({
                credit_name: '',
                credit_amount: '',
                currency: 'USD',
                period: 'monthly',
                description: '',
                is_active: true,
            });
        }
    }, [mode, taxCredit, isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const data = {
            credit_name: formData.credit_name,
            credit_amount: parseFloat(formData.credit_amount),
            currency: formData.currency,
            period: formData.period,
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

    const isCredit_nameReadonly = mode === 'edit';
    const isCurrencyReadonly = mode === 'edit';
    const isPeriodReadonly = mode === 'edit';

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && close()}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {mode === 'create' ? 'Add Tax Credit' : 'Edit Tax Credit'}
                        </DialogTitle>
                        <DialogDescription>
                            {mode === 'create'
                                ? 'Create a new tax credit for payroll calculations.'
                                : 'Update the tax credit amount, description, or status. Credit name, currency, and period cannot be changed.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="credit_name">
                                Credit Name *{' '}
                                {isCredit_nameReadonly && (
                                    <span className="text-muted-foreground font-normal">
                                        (read-only)
                                    </span>
                                )}
                            </Label>
                            <Input
                                id="credit_name"
                                value={formData.credit_name}
                                onChange={(e) =>
                                    setFormData({ ...formData, credit_name: e.target.value })
                                }
                                readOnly={isCredit_nameReadonly}
                                className={isCredit_nameReadonly ? 'bg-muted' : ''}
                                required={!isCredit_nameReadonly}
                                placeholder="e.g., PERSONAL_ALLOWANCE, CHILD_ALLOWANCE"
                            />
                            {!isCredit_nameReadonly && (
                                <p className="text-sm text-muted-foreground">
                                    Use uppercase with underscores, no spaces
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="currency">
                                    Currency *{' '}
                                    {isCurrencyReadonly && (
                                        <span className="text-muted-foreground font-normal">
                                            (read-only)
                                        </span>
                                    )}
                                </Label>
                                <Select
                                    value={formData.currency}
                                    onValueChange={(value: 'USD' | 'ZWG') =>
                                        setFormData({ ...formData, currency: value })
                                    }
                                    disabled={isCurrencyReadonly}
                                >
                                    <SelectTrigger
                                        id="currency"
                                        className={isCurrencyReadonly ? 'bg-muted' : ''}
                                    >
                                        <SelectValue placeholder="Select currency" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="USD">USD - US Dollar</SelectItem>
                                        <SelectItem value="ZWG">ZWG - Zimbabwe Gold</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="period">
                                    Period *{' '}
                                    {isPeriodReadonly && (
                                        <span className="text-muted-foreground font-normal">
                                            (read-only)
                                        </span>
                                    )}
                                </Label>
                                <Select
                                    value={formData.period}
                                    onValueChange={(value: 'monthly' | 'annual') =>
                                        setFormData({ ...formData, period: value })
                                    }
                                    disabled={isPeriodReadonly}
                                >
                                    <SelectTrigger
                                        id="period"
                                        className={isPeriodReadonly ? 'bg-muted' : ''}
                                    >
                                        <SelectValue placeholder="Select period" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="monthly">Monthly</SelectItem>
                                        <SelectItem value="annual">Annual</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="credit_amount">Credit Amount *</Label>
                            <Input
                                id="credit_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value={formData.credit_amount}
                                onChange={(e) =>
                                    setFormData({ ...formData, credit_amount: e.target.value })
                                }
                                placeholder="0.00"
                                required
                            />
                            <p className="text-sm text-muted-foreground">
                                Amount in {formData.currency}
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={formData.description}
                                onChange={(e) =>
                                    setFormData({ ...formData, description: e.target.value })
                                }
                                rows={3}
                                placeholder="Describe what this tax credit is for..."
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

                        <div className="flex items-start gap-2 p-3 bg-muted rounded-md">
                            <Info className="h-4 w-4 mt-0.5 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                Tax credits are automatically applied during payroll calculations. Changes
                                to credit amounts will affect future payroll runs.
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit">
                            {mode === 'create' ? 'Create Tax Credit' : 'Update Tax Credit'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
