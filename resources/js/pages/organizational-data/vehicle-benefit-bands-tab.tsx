import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import { Card, CardContent } from '@/components/ui/card';
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
    type VehicleBenefitBand,
    useCreateOrganizationalItem,
    useDeleteOrganizationalItem,
    useUpdateOrganizationalItem,
    useVehicleBenefitBands,
} from '@/hooks/queries/use-organizational-data';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';
import { create } from 'zustand';

interface VehicleBenefitBandDialogStore {
    isOpen: boolean;
    mode: 'create' | 'edit';
    bandData: Partial<VehicleBenefitBand> | null;
    openCreate: () => void;
    openEdit: (band: VehicleBenefitBand) => void;
    close: () => void;
}

const useVehicleBenefitBandDialog = create<VehicleBenefitBandDialogStore>((set) => ({
    isOpen: false,
    mode: 'create',
    bandData: null,
    openCreate: () => set({ isOpen: true, mode: 'create', bandData: null }),
    openEdit: (band) => set({ isOpen: true, mode: 'edit', bandData: band }),
    close: () => set({ isOpen: false, bandData: null }),
}));

export function VehicleBenefitBandsTab() {
    const bands = useVehicleBenefitBands();
    const { openCreate, openEdit } = useVehicleBenefitBandDialog();
    const deleteMutation = useDeleteOrganizationalItem('vehicle benefit band', '/api/vehicle-benefit-bands');

    const handleDelete = (id: string) => {
        if (confirm('Are you sure you want to delete this vehicle benefit band?')) {
            deleteMutation.mutate(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Vehicle Benefit Bands</h2>
                    <p className="text-muted-foreground">Manage vehicle benefit taxation based on engine capacity</p>
                </div>
                <Button onClick={openCreate}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Benefit Band
                </Button>
            </div>

            <Card>
                <CardContent className="pt-6">
                    {bands.length === 0 ? (
                        <div className="flex items-center justify-center py-12">
                            <div className="text-center">
                                <p className="text-muted-foreground mb-4">No vehicle benefit bands found</p>
                                <Button onClick={openCreate}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Add Benefit Band
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Engine Capacity Range (cc)</TableHead>
                                    <TableHead>Benefit Amount</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead>Period</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {bands.map((band) => (
                                    <TableRow key={band.id}>
                                        <TableCell className="font-medium">
                                            {band.engine_capacity_min.toLocaleString()} - {' '}
                                            {band.engine_capacity_max?.toLocaleString() ?? '∞'}
                                        </TableCell>
                                        <TableCell>${band.benefit_amount.toFixed(2)}</TableCell>
                                        <TableCell>{band.currency}</TableCell>
                                        <TableCell className="capitalize">{band.period}</TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {band.description || '-'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => openEdit(band)}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(band.id)}
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

            <VehicleBenefitBandDialog />
        </div>
    );
}

function VehicleBenefitBandDialog() {
    const { isOpen, mode, bandData, close } = useVehicleBenefitBandDialog();
    const createMutation = useCreateOrganizationalItem('vehicle benefit band', '/api/vehicle-benefit-bands');
    const updateMutation = useUpdateOrganizationalItem(
        'vehicle benefit band',
        '/api/vehicle-benefit-bands',
        bandData?.id ?? ''
    );

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);

        const data = {
            engine_capacity_min: parseInt(formData.get('engine_capacity_min') as string),
            engine_capacity_max: formData.get('engine_capacity_max')
                ? parseInt(formData.get('engine_capacity_max') as string)
                : null,
            benefit_amount: parseFloat(formData.get('benefit_amount') as string),
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
                            {mode === 'create' ? 'Add Vehicle Benefit Band' : 'Edit Vehicle Benefit Band'}
                        </DialogTitle>
                        <DialogDescription>
                            {mode === 'create'
                                ? 'Create a new vehicle benefit band'
                                : 'Update vehicle benefit band details'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="engine_capacity_min">Min Engine Capacity (cc) *</Label>
                                <Input
                                    id="engine_capacity_min"
                                    name="engine_capacity_min"
                                    type="number"
                                    min="0"
                                    defaultValue={bandData?.engine_capacity_min ?? ''}
                                    required
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="engine_capacity_max">Max Engine Capacity (cc)</Label>
                                <Input
                                    id="engine_capacity_max"
                                    name="engine_capacity_max"
                                    type="number"
                                    min="0"
                                    defaultValue={bandData?.engine_capacity_max ?? ''}
                                    placeholder="Leave blank for unlimited"
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="benefit_amount">Benefit Amount *</Label>
                            <Input
                                id="benefit_amount"
                                name="benefit_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={bandData?.benefit_amount ?? ''}
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="currency">Currency *</Label>
                                <Select
                                    name="currency"
                                    defaultValue={bandData?.currency ?? 'USD'}
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
                                    defaultValue={bandData?.period ?? 'monthly'}
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
                                defaultValue={bandData?.description ?? ''}
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
