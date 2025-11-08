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
    type CompanyBankDetail,
    useCompanyBankDetails,
    useCreateOrganizationalItem,
    useDeleteOrganizationalItem,
    useUpdateOrganizationalItem,
} from '@/hooks/queries/use-organizational-data';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';
import { create } from 'zustand';

interface CompanyBankDetailDialogStore {
    isOpen: boolean;
    mode: 'create' | 'edit';
    bankDetailData: Partial<CompanyBankDetail> | null;
    openCreate: () => void;
    openEdit: (bankDetail: CompanyBankDetail) => void;
    close: () => void;
}

const useCompanyBankDetailDialog = create<CompanyBankDetailDialogStore>((set) => ({
    isOpen: false,
    mode: 'create',
    bankDetailData: null,
    openCreate: () => set({ isOpen: true, mode: 'create', bankDetailData: null }),
    openEdit: (bankDetail) => set({ isOpen: true, mode: 'edit', bankDetailData: bankDetail }),
    close: () => set({ isOpen: false, bankDetailData: null }),
}));

export function CompanyBankDetailsTab() {
    const bankDetails = useCompanyBankDetails();
    const { openCreate, openEdit } = useCompanyBankDetailDialog();
    const deleteMutation = useDeleteOrganizationalItem('company bank detail', '/api/company-bank-details');

    const handleDelete = (id: string) => {
        if (confirm('Are you sure you want to delete this bank detail?')) {
            deleteMutation.mutate(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Company Bank Details</h2>
                    <p className="text-muted-foreground">Manage company banking information for payments</p>
                </div>
                <Button onClick={openCreate}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Bank Detail
                </Button>
            </div>

            <Card>
                <CardContent className="pt-6">
                    {bankDetails.length === 0 ? (
                        <div className="flex items-center justify-center py-12">
                            <div className="text-center">
                                <p className="text-muted-foreground mb-4">No bank details found</p>
                                <Button onClick={openCreate}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Add Bank Detail
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Bank Name</TableHead>
                                    <TableHead>Account Name</TableHead>
                                    <TableHead>Account Number</TableHead>
                                    <TableHead>Branch</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {bankDetails.map((detail) => (
                                    <TableRow key={detail.id}>
                                        <TableCell className="font-medium">{detail.bank_name}</TableCell>
                                        <TableCell>{detail.account_name}</TableCell>
                                        <TableCell>{detail.account_number}</TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {detail.branch_name || '-'}
                                        </TableCell>
                                        <TableCell>{detail.currency}</TableCell>
                                        <TableCell>
                                            {detail.is_primary && (
                                                <Badge variant="default">Primary</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => openEdit(detail)}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(detail.id)}
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

            <CompanyBankDetailDialog />
        </div>
    );
}

function CompanyBankDetailDialog() {
    const { isOpen, mode, bankDetailData, close } = useCompanyBankDetailDialog();
    const createMutation = useCreateOrganizationalItem('company bank detail', '/api/company-bank-details');
    const updateMutation = useUpdateOrganizationalItem(
        'company bank detail',
        '/api/company-bank-details',
        bankDetailData?.id ?? ''
    );

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);

        const data = {
            bank_name: formData.get('bank_name') as string,
            account_name: formData.get('account_name') as string,
            account_number: formData.get('account_number') as string,
            branch_name: formData.get('branch_name') as string || undefined,
            branch_code: formData.get('branch_code') as string || undefined,
            swift_code: formData.get('swift_code') as string || undefined,
            currency: formData.get('currency') as 'USD' | 'ZWG',
            is_primary: formData.get('is_primary') === 'on',
            is_active: true,
            description: formData.get('description') as string || undefined,
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
            <DialogContent className="sm:max-w-[600px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {mode === 'create' ? 'Add Bank Detail' : 'Edit Bank Detail'}
                        </DialogTitle>
                        <DialogDescription>
                            {mode === 'create'
                                ? 'Add a new bank account for your company'
                                : 'Update bank account details'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="bank_name">Bank Name *</Label>
                            <Input
                                id="bank_name"
                                name="bank_name"
                                defaultValue={bankDetailData?.bank_name ?? ''}
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="account_name">Account Name *</Label>
                                <Input
                                    id="account_name"
                                    name="account_name"
                                    defaultValue={bankDetailData?.account_name ?? ''}
                                    required
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="account_number">Account Number *</Label>
                                <Input
                                    id="account_number"
                                    name="account_number"
                                    defaultValue={bankDetailData?.account_number ?? ''}
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="branch_name">Branch Name</Label>
                                <Input
                                    id="branch_name"
                                    name="branch_name"
                                    defaultValue={bankDetailData?.branch_name ?? ''}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="branch_code">Branch Code</Label>
                                <Input
                                    id="branch_code"
                                    name="branch_code"
                                    defaultValue={bankDetailData?.branch_code ?? ''}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="swift_code">SWIFT/BIC Code</Label>
                            <Input
                                id="swift_code"
                                name="swift_code"
                                defaultValue={bankDetailData?.swift_code ?? ''}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="currency">Currency *</Label>
                            <Select
                                name="currency"
                                defaultValue={bankDetailData?.currency ?? 'USD'}
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
                            <Label htmlFor="description">Description</Label>
                            <Input
                                id="description"
                                name="description"
                                defaultValue={bankDetailData?.description ?? ''}
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_primary"
                                name="is_primary"
                                defaultChecked={bankDetailData?.is_primary ?? false}
                            />
                            <Label htmlFor="is_primary" className="cursor-pointer">
                                Set as primary bank account
                            </Label>
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
