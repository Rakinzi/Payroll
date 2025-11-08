import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { create } from 'zustand';
import AppLayout from '@/components/layouts/app-layout';
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
import { Building2, Info, MoreHorizontal, Plus, Shield } from 'lucide-react';
import {
    ACCOUNT_TYPE_COLORS,
    ACCOUNT_TYPES,
    CompanyBankDetail,
    CompanyBankDetailData,
    CURRENCIES,
    CURRENCY_COLORS,
    useCreateCompanyBankDetail,
    useDeleteCompanyBankDetail,
    useSetDefaultBankAccount,
    useUpdateCompanyBankDetail,
} from '@/hooks/queries/use-company-bank-details';

interface CompanyBankDetailsPageProps {
    bankDetails: CompanyBankDetail[];
    costCenter: {
        id: string;
        center_name: string;
        center_code: string;
    } | null;
    accountTypes: string[];
    currencies: string[];
}

interface DialogState {
    formDialogOpen: boolean;
    deleteDialogOpen: boolean;
    selectedBankDetail: CompanyBankDetail | null;
    setFormDialogOpen: (open: boolean) => void;
    setDeleteDialogOpen: (open: boolean) => void;
    setSelectedBankDetail: (bankDetail: CompanyBankDetail | null) => void;
}

const useDialogStore = create<DialogState>((set) => ({
    formDialogOpen: false,
    deleteDialogOpen: false,
    selectedBankDetail: null,
    setFormDialogOpen: (open) => set({ formDialogOpen: open }),
    setDeleteDialogOpen: (open) => set({ deleteDialogOpen: open }),
    setSelectedBankDetail: (bankDetail) => set({ selectedBankDetail: bankDetail }),
}));

function BankDetailFormDialog() {
    const { formDialogOpen, setFormDialogOpen, selectedBankDetail, setSelectedBankDetail } =
        useDialogStore();

    const [bankName, setBankName] = useState(selectedBankDetail?.bank_name || '');
    const [branchName, setBranchName] = useState(selectedBankDetail?.branch_name || '');
    const [branchCode, setBranchCode] = useState(selectedBankDetail?.branch_code || '');
    const [accountNumber, setAccountNumber] = useState(selectedBankDetail?.account_number || '');
    const [accountType, setAccountType] = useState<'Current' | 'Nostro' | 'FCA'>(
        selectedBankDetail?.account_type || 'Current'
    );
    const [accountCurrency, setAccountCurrency] = useState<'RTGS' | 'ZWL' | 'USD'>(
        selectedBankDetail?.account_currency || 'USD'
    );
    const [isDefault, setIsDefault] = useState(selectedBankDetail?.is_default || false);

    const createMutation = useCreateCompanyBankDetail();
    const updateMutation = useUpdateCompanyBankDetail(selectedBankDetail?.id || '');

    // Update form when selected bank detail changes
    if (selectedBankDetail && bankName === '' && formDialogOpen) {
        setBankName(selectedBankDetail.bank_name);
        setBranchName(selectedBankDetail.branch_name);
        setBranchCode(selectedBankDetail.branch_code);
        setAccountNumber(selectedBankDetail.account_number);
        setAccountType(selectedBankDetail.account_type);
        setAccountCurrency(selectedBankDetail.account_currency);
        setIsDefault(selectedBankDetail.is_default);
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        const data: CompanyBankDetailData = {
            bank_name: bankName,
            branch_name: branchName,
            branch_code: branchCode,
            account_number: accountNumber,
            account_type: accountType,
            account_currency: accountCurrency,
            is_default: isDefault,
        };

        try {
            if (selectedBankDetail) {
                await updateMutation.mutateAsync(data);
            } else {
                await createMutation.mutateAsync(data);
            }
            handleClose();
        } catch (error) {
            console.error('Failed to save bank detail:', error);
        }
    };

    const handleClose = () => {
        setFormDialogOpen(false);
        setSelectedBankDetail(null);
        setBankName('');
        setBranchName('');
        setBranchCode('');
        setAccountNumber('');
        setAccountType('Current');
        setAccountCurrency('USD');
        setIsDefault(false);
    };

    const isLoading = createMutation.isPending || updateMutation.isPending;

    return (
        <Dialog open={formDialogOpen} onOpenChange={setFormDialogOpen}>
            <DialogContent className="sm:max-w-[550px]">
                <DialogHeader>
                    <DialogTitle>
                        {selectedBankDetail ? 'Edit Bank Account' : 'Add Bank Account'}
                    </DialogTitle>
                    <DialogDescription>
                        {selectedBankDetail
                            ? 'Update the bank account details.'
                            : 'Add a new bank account for payroll disbursements.'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="bank_name">Bank Name *</Label>
                            <Input
                                id="bank_name"
                                value={bankName}
                                onChange={(e) => setBankName(e.target.value)}
                                placeholder="e.g., Central Bank"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="branch_name">Branch Name *</Label>
                                <Input
                                    id="branch_name"
                                    value={branchName}
                                    onChange={(e) => setBranchName(e.target.value)}
                                    placeholder="e.g., Main Branch"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="branch_code">Branch Code *</Label>
                                <Input
                                    id="branch_code"
                                    value={branchCode}
                                    onChange={(e) => setBranchCode(e.target.value)}
                                    placeholder="e.g., 001"
                                    maxLength={10}
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="account_number">Account Number *</Label>
                            <Input
                                id="account_number"
                                value={accountNumber}
                                onChange={(e) => setAccountNumber(e.target.value)}
                                placeholder="Enter full account number"
                                minLength={10}
                                maxLength={20}
                                className="font-mono"
                                required
                            />
                            <p className="text-xs text-muted-foreground">
                                Account number will be encrypted and displayed as masked
                            </p>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="account_type">Account Type *</Label>
                                <Select
                                    value={accountType}
                                    onValueChange={(value) =>
                                        setAccountType(value as 'Current' | 'Nostro' | 'FCA')
                                    }
                                >
                                    <SelectTrigger id="account_type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {ACCOUNT_TYPES.map((type) => (
                                            <SelectItem key={type} value={type}>
                                                {type} Account
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="account_currency">Currency *</Label>
                                <Select
                                    value={accountCurrency}
                                    onValueChange={(value) =>
                                        setAccountCurrency(value as 'RTGS' | 'ZWL' | 'USD')
                                    }
                                >
                                    <SelectTrigger id="account_currency">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="USD">USD - US Dollar</SelectItem>
                                        <SelectItem value="ZWL">ZWL - Zimbabwe Dollar</SelectItem>
                                        <SelectItem value="RTGS">
                                            RTGS - Real Time Gross Settlement
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_default"
                                checked={isDefault}
                                onCheckedChange={(checked) => setIsDefault(checked as boolean)}
                            />
                            <div className="space-y-1">
                                <Label htmlFor="is_default" className="cursor-pointer">
                                    Set as default account
                                </Label>
                                <p className="text-xs text-muted-foreground">
                                    Only one account can be set as default. This account will be used
                                    for automated transactions.
                                </p>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading
                                ? 'Saving...'
                                : selectedBankDetail
                                  ? 'Update Bank Account'
                                  : 'Add Bank Account'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteBankDetailDialog() {
    const { deleteDialogOpen, setDeleteDialogOpen, selectedBankDetail, setSelectedBankDetail } =
        useDialogStore();

    const deleteMutation = useDeleteCompanyBankDetail();

    const handleDelete = async () => {
        if (!selectedBankDetail) return;

        try {
            await deleteMutation.mutateAsync(selectedBankDetail.id);
            setDeleteDialogOpen(false);
            setSelectedBankDetail(null);
        } catch (error) {
            console.error('Failed to delete bank detail:', error);
        }
    };

    return (
        <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the bank account
                        for {selectedBankDetail?.bank_name} ({selectedBankDetail?.masked_account_number}).
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

export default function CompanyBankDetailsIndex() {
    const { bankDetails, costCenter } = usePage<CompanyBankDetailsPageProps>().props;
    const { setFormDialogOpen, setDeleteDialogOpen, setSelectedBankDetail } = useDialogStore();

    const setDefaultMutation = useSetDefaultBankAccount();

    const handleAdd = () => {
        setSelectedBankDetail(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (bankDetail: CompanyBankDetail) => {
        setSelectedBankDetail(bankDetail);
        setFormDialogOpen(true);
    };

    const handleDelete = (bankDetail: CompanyBankDetail) => {
        setSelectedBankDetail(bankDetail);
        setDeleteDialogOpen(true);
    };

    const handleSetDefault = async (bankDetail: CompanyBankDetail) => {
        try {
            await setDefaultMutation.mutateAsync(bankDetail.id);
        } catch (error) {
            console.error('Failed to set default:', error);
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Company Bank Details
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Manage banking accounts for {costCenter?.center_name}
                        </p>
                    </div>
                    <Button onClick={handleAdd}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Bank Account
                    </Button>
                </div>
            }
        >
            <Head title="Company Bank Details" />

            <div className="space-y-6">
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Bank Account Management:</strong> These accounts are used for payroll
                        disbursements and other company financial operations. The default account is
                        used for automated transactions.
                    </AlertDescription>
                </Alert>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            Bank Accounts
                        </CardTitle>
                        <CardDescription>
                            Configure bank accounts for financial operations and payroll processing
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Bank Information</TableHead>
                                    <TableHead>Account Number</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {bankDetails.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-center text-muted-foreground"
                                        >
                                            No bank accounts configured. Add your first bank account to
                                            get started.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    bankDetails.map((bankDetail) => (
                                        <TableRow key={bankDetail.id}>
                                            <TableCell>
                                                <div className="flex flex-col">
                                                    <div className="flex items-center gap-2 font-medium">
                                                        {bankDetail.bank_name}
                                                        {bankDetail.is_default && (
                                                            <Badge variant="default" className="gap-1">
                                                                <Shield className="h-3 w-3" />
                                                                Default
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <span className="text-sm text-muted-foreground">
                                                        {bankDetail.branch_name} -{' '}
                                                        {bankDetail.branch_code}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <span className="font-mono text-sm">
                                                    {bankDetail.masked_account_number}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        ACCOUNT_TYPE_COLORS[bankDetail.account_type]
                                                    }
                                                >
                                                    {bankDetail.account_type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        CURRENCY_COLORS[bankDetail.account_currency]
                                                    }
                                                >
                                                    {bankDetail.account_currency}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                            <span className="sr-only">Actions</span>
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                        <DropdownMenuItem
                                                            onClick={() => handleEdit(bankDetail)}
                                                        >
                                                            Edit
                                                        </DropdownMenuItem>
                                                        {!bankDetail.is_default && (
                                                            <>
                                                                <DropdownMenuItem
                                                                    onClick={() =>
                                                                        handleSetDefault(bankDetail)
                                                                    }
                                                                >
                                                                    Set as Default
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    onClick={() =>
                                                                        handleDelete(bankDetail)
                                                                    }
                                                                    className="text-destructive"
                                                                >
                                                                    Delete
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
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

            <BankDetailFormDialog />
            <DeleteBankDetailDialog />
        </AppLayout>
    );
}
