import { Head, usePage } from '@inertiajs/react';
import { useDialog } from '@/hooks/use-dialog';
import { useState } from 'react';
import { create } from 'zustand';
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
import { Car, DollarSign, Info, MoreHorizontal, Plus, TrendingUp } from 'lucide-react';
import {
    Currency,
    CURRENCIES,
    Period,
    PERIODS,
    VehicleBenefitBand,
    VehicleBenefitBandData,
    useCreateVehicleBenefitBand,
    useDeleteVehicleBenefitBand,
    useUpdateVehicleBenefitBand,
} from '@/hooks/queries/use-vehicle-benefits';

interface VehicleBenefitsPageProps {
    benefitBands: VehicleBenefitBand[];
    supportedCurrencies: string[];
    supportedPeriods: string[];
    filters: {
        currency?: string;
        period?: string;
        active_only?: boolean;
    };
}

interface DialogState {
    formDialogOpen: boolean;
    deleteDialogOpen: boolean;
    selectedBand: VehicleBenefitBand | null;
    setFormDialogOpen: (open: boolean) => void;
    setDeleteDialogOpen: (open: boolean) => void;
    setSelectedBand: (band: VehicleBenefitBand | null) => void;
}

const useDialogStore = create<DialogState>((set) => ({
    formDialogOpen: false,
    deleteDialogOpen: false,
    selectedBand: null,
    setFormDialogOpen: (open) => set({ formDialogOpen: open }),
    setDeleteDialogOpen: (open) => set({ deleteDialogOpen: open }),
    setSelectedBand: (band) => set({ selectedBand: band }),
}));

function VehicleBenefitFormDialog() {
    const { formDialogOpen, setFormDialogOpen, selectedBand, setSelectedBand } = useDialogStore();

    const [engineCapacityMin, setEngineCapacityMin] = useState(selectedBand?.engine_capacity_min || 0);
    const [engineCapacityMax, setEngineCapacityMax] = useState<number | null>(
        selectedBand?.engine_capacity_max ?? null
    );
    const [benefitAmount, setBenefitAmount] = useState(selectedBand?.benefit_amount || 0);
    const [currency, setCurrency] = useState<Currency>(
        (selectedBand?.currency as Currency) || 'USD'
    );
    const [period, setPeriod] = useState<Period>((selectedBand?.period as Period) || 'monthly');
    const [description, setDescription] = useState(selectedBand?.description || '');
    const [isActive, setIsActive] = useState(selectedBand?.is_active ?? true);

    const createMutation = useCreateVehicleBenefitBand();
    const updateMutation = useUpdateVehicleBenefitBand(selectedBand?.id || '');
    const dialog = useDialog();

    // Update form when selected band changes
    if (selectedBand && engineCapacityMin === 0 && formDialogOpen) {
        setEngineCapacityMin(selectedBand.engine_capacity_min);
        setEngineCapacityMax(selectedBand.engine_capacity_max);
        setBenefitAmount(selectedBand.benefit_amount);
        setCurrency(selectedBand.currency as Currency);
        setPeriod(selectedBand.period as Period);
        setDescription(selectedBand.description || '');
        setIsActive(selectedBand.is_active);
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (engineCapacityMax !== null && engineCapacityMax <= engineCapacityMin) {
            dialog.alert('Maximum capacity must be greater than minimum capacity', 'Validation Error');
            return;
        }

        const data: VehicleBenefitBandData = {
            engine_capacity_min: engineCapacityMin,
            engine_capacity_max: engineCapacityMax,
            benefit_amount: benefitAmount,
            currency,
            period,
            description: description || null,
            is_active: isActive,
        };

        try {
            if (selectedBand) {
                await updateMutation.mutateAsync(data);
            } else {
                await createMutation.mutateAsync(data);
            }
            handleClose();
        } catch (error) {
            console.error('Failed to save vehicle benefit band:', error);
        }
    };

    const handleClose = () => {
        setFormDialogOpen(false);
        setSelectedBand(null);
        setEngineCapacityMin(0);
        setEngineCapacityMax(null);
        setBenefitAmount(0);
        setCurrency('USD');
        setPeriod('monthly');
        setDescription('');
        setIsActive(true);
    };

    const isLoading = createMutation.isPending || updateMutation.isPending;

    return (
        <Dialog open={formDialogOpen} onOpenChange={setFormDialogOpen}>
            <DialogContent className="sm:max-w-[550px]">
                <DialogHeader>
                    <DialogTitle>
                        {selectedBand ? 'Edit Vehicle Benefit Band' : 'Add Vehicle Benefit Band'}
                    </DialogTitle>
                    <DialogDescription>
                        {selectedBand
                            ? 'Update the vehicle benefit band configuration.'
                            : 'Create a new vehicle benefit band for tax calculation.'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="engine_capacity_min">Minimum Capacity (cc) *</Label>
                                <Input
                                    id="engine_capacity_min"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value={engineCapacityMin}
                                    onChange={(e) => setEngineCapacityMin(parseInt(e.target.value) || 0)}
                                    placeholder="e.g., 0"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="engine_capacity_max">Maximum Capacity (cc)</Label>
                                <Input
                                    id="engine_capacity_max"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value={engineCapacityMax ?? ''}
                                    onChange={(e) =>
                                        setEngineCapacityMax(
                                            e.target.value ? parseInt(e.target.value) : null
                                        )
                                    }
                                    placeholder="Leave empty for unlimited"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Leave empty for top band
                                </p>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="benefit_amount">Monthly Benefit Amount *</Label>
                            <div className="relative">
                                <DollarSign className="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="benefit_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={benefitAmount}
                                    onChange={(e) => setBenefitAmount(parseFloat(e.target.value) || 0)}
                                    className="pl-8"
                                    placeholder="0.00"
                                    required
                                />
                            </div>
                            <p className="text-xs text-amber-600">
                                This amount will be added to employee taxable income
                            </p>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="currency">Currency *</Label>
                                <Select value={currency} onValueChange={(value: Currency) => setCurrency(value)}>
                                    <SelectTrigger id="currency">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {CURRENCIES.map((curr) => (
                                            <SelectItem key={curr} value={curr}>
                                                {curr}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="period">Period *</Label>
                                <Select value={period} onValueChange={(value: Period) => setPeriod(value)}>
                                    <SelectTrigger id="period">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {PERIODS.map((p) => (
                                            <SelectItem key={p} value={p}>
                                                {p.charAt(0).toUpperCase() + p.slice(1)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Optional description for this benefit band"
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
                            {isLoading ? 'Saving...' : selectedBand ? 'Update Band' : 'Add Band'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteBandDialog() {
    const { deleteDialogOpen, setDeleteDialogOpen, selectedBand, setSelectedBand } = useDialogStore();
    const deleteMutation = useDeleteVehicleBenefitBand();

    const handleDelete = async () => {
        if (!selectedBand) return;

        try {
            await deleteMutation.mutateAsync(selectedBand.id);
            setDeleteDialogOpen(false);
            setSelectedBand(null);
        } catch (error) {
            console.error('Failed to delete vehicle benefit band:', error);
        }
    };

    return (
        <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the vehicle benefit
                        band for capacity range {selectedBand?.capacity_range}.
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

export default function VehicleBenefitsIndex() {
    const { benefitBands } = usePage<VehicleBenefitsPageProps>().props;
    const { setFormDialogOpen, setDeleteDialogOpen, setSelectedBand } = useDialogStore();

    const handleAdd = () => {
        setSelectedBand(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (band: VehicleBenefitBand) => {
        setSelectedBand(band);
        setFormDialogOpen(true);
    };

    const handleDelete = (band: VehicleBenefitBand) => {
        setSelectedBand(band);
        setDeleteDialogOpen(true);
    };

    // Group bands by currency and period
    const groupedBands = benefitBands.reduce((acc, band) => {
        const key = `${band.currency}-${band.period}`;
        if (!acc[key]) {
            acc[key] = [];
        }
        acc[key].push(band);
        return acc;
    }, {} as Record<string, VehicleBenefitBand[]>);

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Vehicle Benefits Bands
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Configure tax benefit amounts based on vehicle engine capacity
                        </p>
                    </div>
                    <Button onClick={handleAdd}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Benefit Band
                    </Button>
                </div>
            }
        >
            <Head title="Vehicle Benefits Bands" />

            <div className="space-y-6">
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Vehicle Benefits:</strong> These bands determine the monthly taxable
                        benefit amount for company-provided vehicles based on engine capacity. Benefits
                        are added to employee taxable income for PAYE calculations.
                    </AlertDescription>
                </Alert>

                {Object.entries(groupedBands).map(([key, bands]) => {
                    const [currency, period] = key.split('-');
                    return (
                        <Card key={key}>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-2">
                                            <Car className="h-5 w-5" />
                                            {currency} - {period.charAt(0).toUpperCase() + period.slice(1)}
                                        </CardTitle>
                                        <CardDescription>
                                            Vehicle benefit bands for {currency} {period} calculations
                                        </CardDescription>
                                    </div>
                                    <Badge variant="secondary">
                                        <TrendingUp className="mr-1 h-3 w-3" />
                                        {bands.length} band{bands.length !== 1 ? 's' : ''}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Capacity Range</TableHead>
                                            <TableHead>Benefit Amount</TableHead>
                                            <TableHead>Description</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {bands.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={5}
                                                    className="text-center text-muted-foreground"
                                                >
                                                    No benefit bands configured for this currency and period
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            bands.map((band) => (
                                                <TableRow key={band.id}>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <Car className="h-4 w-4 text-muted-foreground" />
                                                            <span className="font-medium">
                                                                {band.capacity_range}
                                                            </span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                                                            <span className="font-semibold text-green-600">
                                                                {band.formatted_benefit_amount}
                                                            </span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="text-sm text-muted-foreground">
                                                            {band.description || '-'}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={band.is_active ? 'default' : 'outline'}
                                                        >
                                                            {band.is_active ? 'Active' : 'Inactive'}
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
                                                                    onClick={() => handleEdit(band)}
                                                                >
                                                                    Edit
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    onClick={() => handleDelete(band)}
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
                    );
                })}

                {Object.keys(groupedBands).length === 0 && (
                    <Card>
                        <CardContent className="py-12">
                            <div className="text-center">
                                <Car className="mx-auto h-12 w-12 text-muted-foreground" />
                                <h3 className="mt-4 text-lg font-semibold">No benefit bands configured</h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Get started by adding your first vehicle benefit band
                                </p>
                                <Button onClick={handleAdd} className="mt-4">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Add Benefit Band
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <VehicleBenefitFormDialog />
            <DeleteBandDialog />
        </AppLayout>
    );
}
