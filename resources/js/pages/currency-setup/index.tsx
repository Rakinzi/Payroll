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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { ArrowLeftRight, Calendar, DollarSign, Info, MoreHorizontal, Percent, Plus } from 'lucide-react';
import {
    CurrencySplit,
    CurrencySplitData,
    ExchangeRate,
    ExchangeRateData,
    useCreateCurrencySplit,
    useCreateExchangeRate,
    useDeleteCurrencySplit,
    useDeleteExchangeRate,
    useUpdateCurrencySplit,
    useUpdateExchangeRate,
} from '@/hooks/queries/use-currency-setup';

interface CurrencySetupPageProps {
    currencySplits: {
        data: CurrencySplit[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    exchangeRates: {
        data: ExchangeRate[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    currentSplit: CurrencySplit | null;
    supportedCurrencies: string[];
    filters: {
        split_active_only?: boolean;
        rate_active_only?: boolean;
        from_currency?: string;
        to_currency?: string;
    };
}

interface DialogState {
    splitFormOpen: boolean;
    splitDeleteOpen: boolean;
    rateFormOpen: boolean;
    rateDeleteOpen: boolean;
    selectedSplit: CurrencySplit | null;
    selectedRate: ExchangeRate | null;
    setSplitFormOpen: (open: boolean) => void;
    setSplitDeleteOpen: (open: boolean) => void;
    setRateFormOpen: (open: boolean) => void;
    setRateDeleteOpen: (open: boolean) => void;
    setSelectedSplit: (split: CurrencySplit | null) => void;
    setSelectedRate: (rate: ExchangeRate | null) => void;
}

const useDialogStore = create<DialogState>((set) => ({
    splitFormOpen: false,
    splitDeleteOpen: false,
    rateFormOpen: false,
    rateDeleteOpen: false,
    selectedSplit: null,
    selectedRate: null,
    setSplitFormOpen: (open) => set({ splitFormOpen: open }),
    setSplitDeleteOpen: (open) => set({ splitDeleteOpen: open }),
    setRateFormOpen: (open) => set({ rateFormOpen: open }),
    setRateDeleteOpen: (open) => set({ rateDeleteOpen: open }),
    setSelectedSplit: (split) => set({ selectedSplit: split }),
    setSelectedRate: (rate) => set({ selectedRate: rate }),
}));

function CurrencySplitFormDialog() {
    const { splitFormOpen, setSplitFormOpen, selectedSplit, setSelectedSplit } = useDialogStore();
    const { auth } = usePage<any>().props;

    const [zwlPercentage, setZwlPercentage] = useState(selectedSplit?.zwl_percentage || 0);
    const [usdPercentage, setUsdPercentage] = useState(selectedSplit?.usd_percentage || 100);
    const [effectiveDate, setEffectiveDate] = useState(
        selectedSplit?.effective_date || new Date().toISOString().split('T')[0]
    );
    const [isActive, setIsActive] = useState(selectedSplit?.is_active ?? true);
    const [notes, setNotes] = useState(selectedSplit?.notes || '');

    const createMutation = useCreateCurrencySplit();
    const updateMutation = useUpdateCurrencySplit(selectedSplit?.id || '');
    const dialog = useDialog();

    // Update form when selected split changes
    if (selectedSplit && zwlPercentage === 0 && usdPercentage === 100 && splitFormOpen) {
        setZwlPercentage(selectedSplit.zwl_percentage);
        setUsdPercentage(selectedSplit.usd_percentage);
        setEffectiveDate(selectedSplit.effective_date);
        setIsActive(selectedSplit.is_active);
        setNotes(selectedSplit.notes || '');
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        const total = parseFloat(zwlPercentage.toString()) + parseFloat(usdPercentage.toString());
        if (Math.abs(total - 100) >= 0.01) {
            dialog.alert('Currency split percentages must total 100%', 'Validation Error');
            return;
        }

        const data: CurrencySplitData = {
            center_id: auth.user.center_id,
            zwl_percentage: parseFloat(zwlPercentage.toString()),
            usd_percentage: parseFloat(usdPercentage.toString()),
            effective_date: effectiveDate,
            is_active: isActive,
            notes: notes || null,
        };

        try {
            if (selectedSplit) {
                await updateMutation.mutateAsync(data);
            } else {
                await createMutation.mutateAsync(data);
            }
            handleClose();
        } catch (error) {
            console.error('Failed to save currency split:', error);
        }
    };

    const handleClose = () => {
        setSplitFormOpen(false);
        setSelectedSplit(null);
        setZwlPercentage(0);
        setUsdPercentage(100);
        setEffectiveDate(new Date().toISOString().split('T')[0]);
        setIsActive(true);
        setNotes('');
    };

    const handleZwlChange = (value: string) => {
        const val = parseFloat(value) || 0;
        setZwlPercentage(val);
        setUsdPercentage(100 - val);
    };

    const handleUsdChange = (value: string) => {
        const val = parseFloat(value) || 0;
        setUsdPercentage(val);
        setZwlPercentage(100 - val);
    };

    const isLoading = createMutation.isPending || updateMutation.isPending;
    const total = parseFloat(zwlPercentage.toString()) + parseFloat(usdPercentage.toString());
    const isValid = Math.abs(total - 100) < 0.01;

    return (
        <Dialog open={splitFormOpen} onOpenChange={setSplitFormOpen}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>
                        {selectedSplit ? 'Edit Currency Split' : 'Add Currency Split'}
                    </DialogTitle>
                    <DialogDescription>
                        {selectedSplit
                            ? 'Update the currency split configuration.'
                            : 'Define the percentage split between ZWG and USD for payroll.'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="zwl_percentage">ZWG Percentage *</Label>
                                <div className="relative">
                                    <Input
                                        id="zwl_percentage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={zwlPercentage}
                                        onChange={(e) => handleZwlChange(e.target.value)}
                                        className="pr-8"
                                        required
                                    />
                                    <Percent className="absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="usd_percentage">USD Percentage *</Label>
                                <div className="relative">
                                    <Input
                                        id="usd_percentage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={usdPercentage}
                                        onChange={(e) => handleUsdChange(e.target.value)}
                                        className="pr-8"
                                        required
                                    />
                                    <Percent className="absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                </div>
                            </div>
                        </div>

                        {!isValid && (
                            <Alert variant="destructive">
                                <AlertDescription>
                                    Total must equal 100%. Current total: {total.toFixed(2)}%
                                </AlertDescription>
                            </Alert>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="effective_date">Effective Date *</Label>
                            <Input
                                id="effective_date"
                                type="date"
                                value={effectiveDate}
                                onChange={(e) => setEffectiveDate(e.target.value)}
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="notes">Notes</Label>
                            <Textarea
                                id="notes"
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                placeholder="Optional notes about this currency split configuration"
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
                        <Button type="submit" disabled={isLoading || !isValid}>
                            {isLoading
                                ? 'Saving...'
                                : selectedSplit
                                  ? 'Update Split'
                                  : 'Add Split'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteSplitDialog() {
    const { splitDeleteOpen, setSplitDeleteOpen, selectedSplit, setSelectedSplit } = useDialogStore();
    const deleteMutation = useDeleteCurrencySplit();

    const handleDelete = async () => {
        if (!selectedSplit) return;

        try {
            await deleteMutation.mutateAsync(selectedSplit.id);
            setSplitDeleteOpen(false);
            setSelectedSplit(null);
        } catch (error) {
            console.error('Failed to delete currency split:', error);
        }
    };

    return (
        <AlertDialog open={splitDeleteOpen} onOpenChange={setSplitDeleteOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the currency split
                        configuration effective from {selectedSplit?.effective_date}.
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

function ExchangeRateFormDialog() {
    const { rateFormOpen, setRateFormOpen, selectedRate, setSelectedRate } = useDialogStore();
    const { supportedCurrencies } = usePage<CurrencySetupPageProps>().props;

    const [fromCurrency, setFromCurrency] = useState(selectedRate?.from_currency || 'USD');
    const [toCurrency, setToCurrency] = useState(selectedRate?.to_currency || 'ZWG');
    const [rate, setRate] = useState(selectedRate?.rate || 0);
    const [effectiveDate, setEffectiveDate] = useState(
        selectedRate?.effective_date || new Date().toISOString().split('T')[0]
    );
    const [isActive, setIsActive] = useState(selectedRate?.is_active ?? true);

    const createMutation = useCreateExchangeRate();
    const updateMutation = useUpdateExchangeRate(selectedRate?.id || '');
    const dialog = useDialog();

    // Update form when selected rate changes
    if (selectedRate && rate === 0 && rateFormOpen) {
        setFromCurrency(selectedRate.from_currency);
        setToCurrency(selectedRate.to_currency);
        setRate(selectedRate.rate);
        setEffectiveDate(selectedRate.effective_date);
        setIsActive(selectedRate.is_active);
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (fromCurrency === toCurrency) {
            dialog.alert('From and To currencies must be different', 'Validation Error');
            return;
        }

        const data: ExchangeRateData = {
            from_currency: fromCurrency,
            to_currency: toCurrency,
            rate: parseFloat(rate.toString()),
            effective_date: effectiveDate,
            is_active: isActive,
        };

        try {
            if (selectedRate) {
                await updateMutation.mutateAsync(data);
            } else {
                await createMutation.mutateAsync(data);
            }
            handleClose();
        } catch (error) {
            console.error('Failed to save exchange rate:', error);
        }
    };

    const handleClose = () => {
        setRateFormOpen(false);
        setSelectedRate(null);
        setFromCurrency('USD');
        setToCurrency('ZWG');
        setRate(0);
        setEffectiveDate(new Date().toISOString().split('T')[0]);
        setIsActive(true);
    };

    const isLoading = createMutation.isPending || updateMutation.isPending;

    return (
        <Dialog open={rateFormOpen} onOpenChange={setRateFormOpen}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>
                        {selectedRate ? 'Edit Exchange Rate' : 'Add Exchange Rate'}
                    </DialogTitle>
                    <DialogDescription>
                        {selectedRate
                            ? 'Update the exchange rate configuration.'
                            : 'Define an exchange rate between two currencies.'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="from_currency">From Currency *</Label>
                                <Select value={fromCurrency} onValueChange={setFromCurrency}>
                                    <SelectTrigger id="from_currency">
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
                                <Label htmlFor="to_currency">To Currency *</Label>
                                <Select value={toCurrency} onValueChange={setToCurrency}>
                                    <SelectTrigger id="to_currency">
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
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="rate">Exchange Rate *</Label>
                            <Input
                                id="rate"
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                value={rate}
                                onChange={(e) => setRate(parseFloat(e.target.value))}
                                placeholder="e.g., 13.500000"
                                required
                            />
                            <p className="text-xs text-muted-foreground">
                                1 {fromCurrency} = {rate || 0} {toCurrency}
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="effective_date">Effective Date *</Label>
                            <Input
                                id="effective_date"
                                type="date"
                                value={effectiveDate}
                                onChange={(e) => setEffectiveDate(e.target.value)}
                                required
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
                        <Button type="submit" disabled={isLoading || fromCurrency === toCurrency}>
                            {isLoading ? 'Saving...' : selectedRate ? 'Update Rate' : 'Add Rate'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteRateDialog() {
    const { rateDeleteOpen, setRateDeleteOpen, selectedRate, setSelectedRate } = useDialogStore();
    const deleteMutation = useDeleteExchangeRate();

    const handleDelete = async () => {
        if (!selectedRate) return;

        try {
            await deleteMutation.mutateAsync(selectedRate.id);
            setRateDeleteOpen(false);
            setSelectedRate(null);
        } catch (error) {
            console.error('Failed to delete exchange rate:', error);
        }
    };

    return (
        <AlertDialog open={rateDeleteOpen} onOpenChange={setRateDeleteOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the exchange rate
                        from {selectedRate?.from_currency} to {selectedRate?.to_currency}.
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

export default function CurrencySetupIndex() {
    const { currencySplits, exchangeRates, currentSplit } = usePage<CurrencySetupPageProps>().props;
    const {
        setSplitFormOpen,
        setSplitDeleteOpen,
        setRateFormOpen,
        setRateDeleteOpen,
        setSelectedSplit,
        setSelectedRate,
    } = useDialogStore();

    const handleAddSplit = () => {
        setSelectedSplit(null);
        setSplitFormOpen(true);
    };

    const handleEditSplit = (split: CurrencySplit) => {
        setSelectedSplit(split);
        setSplitFormOpen(true);
    };

    const handleDeleteSplit = (split: CurrencySplit) => {
        setSelectedSplit(split);
        setSplitDeleteOpen(true);
    };

    const handleAddRate = () => {
        setSelectedRate(null);
        setRateFormOpen(true);
    };

    const handleEditRate = (rate: ExchangeRate) => {
        setSelectedRate(rate);
        setRateFormOpen(true);
    };

    const handleDeleteRate = (rate: ExchangeRate) => {
        setSelectedRate(rate);
        setRateDeleteOpen(true);
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Currency Setup & Management
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Configure currency splits and exchange rates for payroll processing
                    </p>
                </div>
            }
        >
            <Head title="Currency Setup" />

            <div className="space-y-6">
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Currency Configuration:</strong> Define how salaries are split between
                        currencies and set exchange rates for accurate payroll calculations.
                        {currentSplit && (
                            <span className="ml-2">
                                Current split: <strong>{currentSplit.formatted_zwl_percentage}</strong>{' '}
                                ZWG, <strong>{currentSplit.formatted_usd_percentage}</strong> USD
                            </span>
                        )}
                    </AlertDescription>
                </Alert>

                <Tabs defaultValue="splits" className="w-full">
                    <TabsList className="grid w-full grid-cols-2 max-w-md">
                        <TabsTrigger value="splits">Currency Splits</TabsTrigger>
                        <TabsTrigger value="rates">Exchange Rates</TabsTrigger>
                    </TabsList>

                    <TabsContent value="splits" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-2">
                                            <Percent className="h-5 w-5" />
                                            Currency Splits
                                        </CardTitle>
                                        <CardDescription>
                                            Configure the percentage split between ZWG and USD for payroll
                                        </CardDescription>
                                    </div>
                                    <Button onClick={handleAddSplit}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Add Split
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Effective Date</TableHead>
                                            <TableHead>ZWG %</TableHead>
                                            <TableHead>USD %</TableHead>
                                            <TableHead>Notes</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {currencySplits.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={6}
                                                    className="text-center text-muted-foreground"
                                                >
                                                    No currency splits configured. Add your first split to
                                                    get started.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            currencySplits.data.map((split) => (
                                                <TableRow key={split.id}>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <Calendar className="h-4 w-4 text-muted-foreground" />
                                                            {new Date(
                                                                split.effective_date
                                                            ).toLocaleDateString()}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="secondary">
                                                            {split.formatted_zwl_percentage}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="secondary">
                                                            {split.formatted_usd_percentage}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="text-sm text-muted-foreground">
                                                            {split.notes || '-'}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                split.is_active ? 'default' : 'outline'
                                                            }
                                                        >
                                                            {split.is_active ? 'Active' : 'Inactive'}
                                                        </Badge>
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
                                                                    onClick={() => handleEditSplit(split)}
                                                                >
                                                                    Edit
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    onClick={() =>
                                                                        handleDeleteSplit(split)
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
                    </TabsContent>

                    <TabsContent value="rates" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-2">
                                            <ArrowLeftRight className="h-5 w-5" />
                                            Exchange Rates
                                        </CardTitle>
                                        <CardDescription>
                                            Manage exchange rates between different currencies
                                        </CardDescription>
                                    </div>
                                    <Button onClick={handleAddRate}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Add Rate
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Currency Pair</TableHead>
                                            <TableHead>Exchange Rate</TableHead>
                                            <TableHead>Effective Date</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {exchangeRates.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={5}
                                                    className="text-center text-muted-foreground"
                                                >
                                                    No exchange rates configured. Add your first rate to
                                                    get started.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            exchangeRates.data.map((rate) => (
                                                <TableRow key={rate.id}>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2 font-medium">
                                                            <Badge variant="outline">
                                                                {rate.from_currency}
                                                            </Badge>
                                                            <ArrowLeftRight className="h-3 w-3 text-muted-foreground" />
                                                            <Badge variant="outline">
                                                                {rate.to_currency}
                                                            </Badge>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                                                            <span className="font-mono">
                                                                {rate.formatted_rate}
                                                            </span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <Calendar className="h-4 w-4 text-muted-foreground" />
                                                            {new Date(
                                                                rate.effective_date
                                                            ).toLocaleDateString()}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                rate.is_active ? 'default' : 'outline'
                                                            }
                                                        >
                                                            {rate.is_active ? 'Active' : 'Inactive'}
                                                        </Badge>
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
                                                                    onClick={() => handleEditRate(rate)}
                                                                >
                                                                    Edit
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    onClick={() =>
                                                                        handleDeleteRate(rate)
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
                    </TabsContent>
                </Tabs>
            </div>

            <CurrencySplitFormDialog />
            <DeleteSplitDialog />
            <ExchangeRateFormDialog />
            <DeleteRateDialog />
        </AppLayout>
    );
}
