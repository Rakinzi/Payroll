import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    type TaxCredit,
    useCreateOrganizationalItem,
    useDeleteOrganizationalItem,
    useTaxCredits,
    useUpdateOrganizationalItem,
} from '@/hooks/queries/use-organizational-data';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';
import { create } from 'zustand';

interface TaxCreditDialogStore {
    isOpen: boolean;
    mode: 'create' | 'edit';
    taxCreditData: Partial<TaxCredit> | null;
    openCreate: () => void;
    openEdit: (taxCredit: TaxCredit) => void;
    close: () => void;
}

const useTaxCreditDialog = create<TaxCreditDialogStore>((set) => ({
    isOpen: false,
    mode: 'create',
    taxCreditData: null,
    openCreate: () => set({ isOpen: true, mode: 'create', taxCreditData: null }),
    openEdit: (taxCredit) => set({ isOpen: true, mode: 'edit', taxCreditData: taxCredit }),
    close: () => set({ isOpen: false, taxCreditData: null }),
}));

export function TaxCreditsTab() {
    const taxCredits = useTaxCredits();
    const { openCreate, openEdit } = useTaxCreditDialog();
    const deleteMutation = useDeleteOrganizationalItem('tax credit', '/api/tax-credits');
    const dialog = useDialog();

    const handleDelete = async (id: string) => {
        const confirmed = await dialog.confirm('Are you sure you want to delete this tax credit?', {
            title: 'Confirm Deletion',
            confirmText: 'Delete',
            variant: 'destructive',
        });

        if (confirmed) {
            deleteMutation.mutate(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Tax Credits</h2>
                    <p className="text-muted-foreground">Manage tax credit configurations</p>
                </div>
                <Button onClick={openCreate}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Tax Credit
                </Button>
            </div>

            <Card>
                <CardContent className="pt-6">
                    {taxCredits.length === 0 ? (
                        <div className="flex items-center justify-center py-12">
                            <div className="text-center">
                                <p className="text-muted-foreground mb-4">No tax credits found</p>
                                <Button onClick={openCreate}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Add Tax Credit
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead>Period</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {taxCredits.map((credit) => (
                                    <TableRow key={credit.id}>
                                        <TableCell className="font-medium">{credit.credit_name}</TableCell>
                                        <TableCell>${credit.credit_amount.toFixed(2)}</TableCell>
                                        <TableCell>{credit.currency}</TableCell>
                                        <TableCell className="capitalize">{credit.period}</TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {credit.description || '-'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => openEdit(credit)}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(credit.id)}
                                                    disabled={deleteMutation.isPending}
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            <TaxCreditDialog />
        </div>
    );
}

function TaxCreditDialog() {
    const { isOpen, mode, taxCreditData, close } = useTaxCreditDialog();
    const createMutation = useCreateOrganizationalItem('tax credit', '/api/tax-credits');
    const updateMutation = useUpdateOrganizationalItem(
        'tax credit',
        '/api/tax-credits',
        taxCreditData?.id ?? ''
    );

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);

        const data = {
            credit_name: formData.get('credit_name') as string,
            credit_amount: parseFloat(formData.get('credit_amount') as string),
            currency: formData.get('currency') as 'USD' | 'ZWG',
            period: formData.get('period') as 'monthly' | 'annual',
            description: formData.get('description') as string || undefined,
            is_active: true,
        };

        if (mode === 'create') {
            createMutation.mutate(data, {
                onSuccess: () => close(),
            });
        } else {
            updateMutation.mutate(data, {
                onSuccess: () => close(),
            });
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && close()}>
            <DialogContent className="sm:max-w-[500px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {mode === 'create' ? 'Add Tax Credit' : 'Edit Tax Credit'}
                        </DialogTitle>
                        <DialogDescription>
                            {mode === 'create'
                                ? 'Create a new tax credit configuration'
                                : 'Update tax credit details'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="credit_name">Credit Name *</Label>
                            <Input
                                id="credit_name"
                                name="credit_name"
                                defaultValue={taxCreditData?.credit_name ?? ''}
                                required
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="credit_amount">Amount *</Label>
                            <Input
                                id="credit_amount"
                                name="credit_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={taxCreditData?.credit_amount ?? ''}
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="currency">Currency *</Label>
                                <Select
                                    name="currency"
                                    defaultValue={taxCreditData?.currency ?? 'USD'}
                                    required
                                >
                                    <SelectTrigger id="currency">
                                        <SelectValue placeholder="Select currency" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="USD">USD</SelectItem>
                                        <SelectItem value="ZWG">ZWG</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="period">Period *</Label>
                                <Select
                                    name="period"
                                    defaultValue={taxCreditData?.period ?? 'monthly'}
                                    required
                                >
                                    <SelectTrigger id="period">
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
                            <Label htmlFor="description">Description</Label>
                            <Input
                                id="description"
                                name="description"
                                defaultValue={taxCreditData?.description ?? ''}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={createMutation.isPending || updateMutation.isPending}
                        >
                            {mode === 'create' ? 'Create' : 'Update'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
