import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { create } from 'zustand';
import AppLayout from '@/layouts/app-layout';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Edit, Info } from 'lucide-react';
import {
    BandType,
    BAND_TYPE_LABELS,
    TaxBand,
    TaxBandData,
    useUpdateTaxBand,
} from '@/hooks/queries/use-tax-bands';

interface TaxBandsPageProps {
    taxBands: Record<BandType, TaxBand[]>;
    bandTypes: BandType[];
    bandTypeLabels: Record<BandType, string>;
}

interface DialogState {
    editDialogOpen: boolean;
    selectedBand: TaxBand | null;
    selectedBandType: BandType | null;
    setEditDialogOpen: (open: boolean) => void;
    setSelectedBand: (band: TaxBand | null, bandType: BandType | null) => void;
}

const useDialogStore = create<DialogState>((set) => ({
    editDialogOpen: false,
    selectedBand: null,
    selectedBandType: null,
    setEditDialogOpen: (open) => set({ editDialogOpen: open }),
    setSelectedBand: (band, bandType) =>
        set({ selectedBand: band, selectedBandType: bandType }),
}));

function EditTaxBandDialog() {
    const { editDialogOpen, setEditDialogOpen, selectedBand, selectedBandType, setSelectedBand } =
        useDialogStore();

    const [minSalary, setMinSalary] = useState(selectedBand?.min_salary.toString() || '');
    const [maxSalary, setMaxSalary] = useState(
        selectedBand?.max_salary?.toString() || ''
    );
    const [taxRate, setTaxRate] = useState(
        selectedBand ? (selectedBand.tax_rate * 100).toString() : ''
    );
    const [taxAmount, setTaxAmount] = useState(selectedBand?.tax_amount.toString() || '');

    const updateTaxBandMutation = useUpdateTaxBand(
        selectedBandType || 'annual_zwl',
        selectedBand?.id || 0
    );

    // Update form when selected band changes
    if (selectedBand && minSalary === '' && editDialogOpen) {
        setMinSalary(selectedBand.min_salary.toString());
        setMaxSalary(selectedBand.max_salary?.toString() || '');
        setTaxRate((selectedBand.tax_rate * 100).toString());
        setTaxAmount(selectedBand.tax_amount.toString());
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedBand || !selectedBandType) return;

        const data: TaxBandData = {
            min_salary: parseFloat(minSalary),
            max_salary: maxSalary ? parseFloat(maxSalary) : null,
            tax_rate: parseFloat(taxRate) / 100, // Convert percentage to decimal
            tax_amount: parseFloat(taxAmount),
        };

        try {
            await updateTaxBandMutation.mutateAsync(data);
            handleClose();
        } catch (error) {
            console.error('Failed to update tax band:', error);
        }
    };

    const handleClose = () => {
        setEditDialogOpen(false);
        setSelectedBand(null, null);
        setMinSalary('');
        setMaxSalary('');
        setTaxRate('');
        setTaxAmount('');
    };

    const currencyLabel = selectedBandType?.includes('usd') ? 'USD' : 'ZWG';
    const periodLabel = selectedBandType?.includes('annual') ? 'Annual' : 'Monthly';

    return (
        <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>
                        Edit {currencyLabel} {periodLabel} Tax Band
                    </DialogTitle>
                    <DialogDescription>
                        Update the tax band configuration. Changes will affect future payroll
                        calculations.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="min_salary">
                                    Lower Limit ({currencyLabel}) *
                                </Label>
                                <Input
                                    id="min_salary"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={minSalary}
                                    onChange={(e) => setMinSalary(e.target.value)}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="max_salary">Upper Limit ({currencyLabel})</Label>
                                <Input
                                    id="max_salary"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={maxSalary}
                                    onChange={(e) => setMaxSalary(e.target.value)}
                                    placeholder="Leave empty for unlimited"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Leave empty for no upper limit
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="tax_rate">Tax Rate (%) *</Label>
                                <Input
                                    id="tax_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={taxRate}
                                    onChange={(e) => setTaxRate(e.target.value)}
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    Rate applied to income in this band
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tax_amount">
                                    Fixed Deduction ({currencyLabel}) *
                                </Label>
                                <Input
                                    id="tax_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={taxAmount}
                                    onChange={(e) => setTaxAmount(e.target.value)}
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    Fixed amount deducted for this band
                                </p>
                            </div>
                        </div>

                        <Alert>
                            <Info className="h-4 w-4" />
                            <AlertDescription>
                                <strong>Warning:</strong> Changes to tax bands will affect future
                                payroll calculations. Ensure rates comply with current tax
                                regulations.
                            </AlertDescription>
                        </Alert>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={updateTaxBandMutation.isPending}>
                            {updateTaxBandMutation.isPending ? 'Updating...' : 'Update Tax Band'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function TaxBandTable({ bands, bandType }: { bands: TaxBand[]; bandType: BandType }) {
    const { setEditDialogOpen, setSelectedBand } = useDialogStore();

    const handleEdit = (band: TaxBand) => {
        setSelectedBand(band, bandType);
        setEditDialogOpen(true);
    };

    const currencyLabel = bandType.includes('usd') ? 'USD' : 'ZWG';

    // Calculate zero band (tax-free threshold)
    const zeroBandMax = bands.length > 0 ? bands[0].min_salary - 0.01 : 0;

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Lower Limit</TableHead>
                    <TableHead>Upper Limit</TableHead>
                    <TableHead>Rate</TableHead>
                    <TableHead>Fixed Deduction</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {/* Zero rate band (tax-free threshold) */}
                {zeroBandMax > 0 && (
                    <TableRow>
                        <TableCell className="font-medium">
                            {currencyLabel} 0.00
                        </TableCell>
                        <TableCell>{currencyLabel} {zeroBandMax.toFixed(2)}</TableCell>
                        <TableCell>
                            <Badge variant="secondary">0%</Badge>
                        </TableCell>
                        <TableCell>-</TableCell>
                        <TableCell className="text-right">
                            <span className="text-xs text-muted-foreground">Tax-free</span>
                        </TableCell>
                    </TableRow>
                )}

                {/* Actual tax bands */}
                {bands.length === 0 ? (
                    <TableRow>
                        <TableCell colSpan={5} className="text-center text-muted-foreground">
                            No tax bands configured for this type.
                        </TableCell>
                    </TableRow>
                ) : (
                    bands.map((band) => (
                        <TableRow key={band.id}>
                            <TableCell className="font-medium">
                                {currencyLabel} {band.formatted_min_salary}
                            </TableCell>
                            <TableCell>
                                {band.max_salary ? (
                                    `${currencyLabel} ${band.formatted_max_salary}`
                                ) : (
                                    <Badge variant="outline">Above</Badge>
                                )}
                            </TableCell>
                            <TableCell>
                                <Badge>{band.formatted_rate}</Badge>
                            </TableCell>
                            <TableCell>
                                {currencyLabel} {band.tax_amount.toFixed(2)}
                            </TableCell>
                            <TableCell className="text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => handleEdit(band)}
                                >
                                    <Edit className="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    ))
                )}
            </TableBody>
        </Table>
    );
}

export default function TaxBandsIndex() {
    const { taxBands, bandTypes } = usePage<TaxBandsPageProps>().props;

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Tax Bands Management
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Configure progressive tax rates for different currencies and periods
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Tax Bands Management" />

            <div className="space-y-6">
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Tax Calculation:</strong> Tax is calculated progressively across
                        bands. Each band applies its rate to income within its range, plus any
                        fixed deduction amount.
                    </AlertDescription>
                </Alert>

                <Tabs defaultValue="annual_zwl" className="space-y-4">
                    <TabsList className="grid w-full grid-cols-4">
                        {bandTypes.map((type) => (
                            <TabsTrigger key={type} value={type}>
                                {BAND_TYPE_LABELS[type]}
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    {bandTypes.map((type) => (
                        <TabsContent key={type} value={type}>
                            <Card>
                                <CardHeader>
                                    <CardTitle>{BAND_TYPE_LABELS[type]}</CardTitle>
                                    <CardDescription>
                                        Progressive tax bands for{' '}
                                        {type.includes('annual') ? 'annual' : 'monthly'} calculations
                                        in {type.includes('usd') ? 'USD' : 'ZWG'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <TaxBandTable bands={taxBands[type]} bandType={type} />
                                </CardContent>
                            </Card>
                        </TabsContent>
                    ))}
                </Tabs>
            </div>

            <EditTaxBandDialog />
        </AppLayout>
    );
}
