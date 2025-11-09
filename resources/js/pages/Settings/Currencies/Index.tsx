import { useState } from 'react';
import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { PageProps } from '@/types';
import { Button } from '@/components/ui/button';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { AlertCircle, CheckCircle2, MoreHorizontal, Plus, RefreshCw, Search, TrendingDown, TrendingUp, History } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useCurrencyStore } from '@/stores/currency-store';
import {
    useCreateCurrency,
    useUpdateCurrency,
    useDeleteCurrency,
    useToggleCurrencyStatus,
    useSetBaseCurrency,
    Currency,
} from '@/hooks/queries/use-currencies';

interface CurrenciesIndexProps extends PageProps {
    currencies: {
        data: Currency[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

export default function Index({ auth, currencies, filters }: CurrenciesIndexProps) {
    const store = useCurrencyStore();
    const createMutation = useCreateCurrency();
    const updateMutation = useUpdateCurrency(store.selectedCurrency?.currency_id || 0);
    const deleteMutation = useDeleteCurrency();
    const toggleStatusMutation = useToggleCurrencyStatus();
    const setBaseMutation = useSetBaseCurrency();

    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [showHistoryDialog, setShowHistoryDialog] = useState(false);
    const [currencyToDelete, setCurrencyToDelete] = useState<Currency | null>(null);
    const [currencyHistory, setCurrencyHistory] = useState<any[]>([]);
    const [loadingHistory, setLoadingHistory] = useState(false);

    const [formData, setFormData] = useState({
        code: '',
        name: '',
        symbol: '',
        exchange_rate: 1.0,
        decimal_places: 2,
        description: '',
    });

    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState<string>(filters.status || 'all');

    // Handle search
    const handleSearch = () => {
        router.get(
            route('settings.currencies.index'),
            { search: searchTerm, status: statusFilter !== 'all' ? statusFilter : undefined },
            { preserveState: true }
        );
    };

    // Handle status filter
    const handleStatusFilter = (value: string) => {
        setStatusFilter(value);
        router.get(
            route('settings.currencies.index'),
            { search: searchTerm, status: value !== 'all' ? value : undefined },
            { preserveState: true }
        );
    };

    // Open create dialog
    const openCreateDialog = () => {
        setFormData({
            code: '',
            name: '',
            symbol: '',
            exchange_rate: 1.0,
            decimal_places: 2,
            description: '',
        });
        setShowCreateDialog(true);
    };

    // Open edit dialog
    const openEditDialog = (currency: Currency) => {
        store.setSelectedCurrency(currency);
        setFormData({
            code: currency.code,
            name: currency.name,
            symbol: currency.symbol,
            exchange_rate: currency.exchange_rate,
            decimal_places: currency.decimal_places,
            description: currency.description || '',
        });
        setShowEditDialog(true);
    };

    // Open delete dialog
    const openDeleteDialog = (currency: Currency) => {
        setCurrencyToDelete(currency);
        setShowDeleteDialog(true);
    };

    // Open history dialog
    const openHistoryDialog = async (currency: Currency) => {
        store.setSelectedCurrency(currency);
        setShowHistoryDialog(true);
        setLoadingHistory(true);

        try {
            const response = await fetch(route('settings.currencies.history', currency.currency_id));
            const data = await response.json();
            setCurrencyHistory(data.history.data || []);
        } catch (error) {
            console.error('Failed to load history:', error);
        } finally {
            setLoadingHistory(false);
        }
    };

    // Handle create
    const handleCreate = () => {
        createMutation.mutate(formData, {
            onSuccess: () => {
                setShowCreateDialog(false);
                setFormData({
                    code: '',
                    name: '',
                    symbol: '',
                    exchange_rate: 1.0,
                    decimal_places: 2,
                    description: '',
                });
            },
        });
    };

    // Handle update
    const handleUpdate = () => {
        if (!store.selectedCurrency) return;

        updateMutation.mutate(
            {
                name: formData.name,
                symbol: formData.symbol,
                exchange_rate: formData.exchange_rate,
                decimal_places: formData.decimal_places,
                description: formData.description,
            },
            {
                onSuccess: () => {
                    setShowEditDialog(false);
                    store.setSelectedCurrency(null);
                },
            }
        );
    };

    // Handle delete
    const handleDelete = () => {
        if (!currencyToDelete) return;

        deleteMutation.mutate(currencyToDelete.currency_id, {
            onSuccess: () => {
                setShowDeleteDialog(false);
                setCurrencyToDelete(null);
            },
        });
    };

    // Handle toggle status
    const handleToggleStatus = (currency: Currency) => {
        toggleStatusMutation.mutate(currency.currency_id);
    };

    // Handle set as base
    const handleSetAsBase = (currency: Currency) => {
        if (confirm(`Are you sure you want to set ${currency.code} as the base currency?`)) {
            setBaseMutation.mutate(currency.currency_id);
        }
    };

    // Handle update from API
    const handleUpdateFromApi = (currency: Currency) => {
        router.post(route('settings.currencies.update-from-api', currency.currency_id), {}, {
            preserveState: true,
        });
    };

    // Handle update all from API
    const handleUpdateAllFromApi = () => {
        if (confirm('Update all currency exchange rates from API?')) {
            router.post(route('settings.currencies.update-all-from-api'), {}, {
                preserveState: true,
            });
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Currency Management" />

            <div className="container mx-auto py-6 space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold">Currency Management</h1>
                        <p className="text-muted-foreground mt-1">
                            Manage currencies and exchange rates for the payroll system
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button onClick={handleUpdateAllFromApi} variant="outline">
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Update All Rates
                        </Button>
                        {auth.user.role === 'admin' && (
                            <Button onClick={openCreateDialog}>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Currency
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search by code, name, or symbol..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <Select value={statusFilter} onValueChange={handleStatusFilter}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Filter by status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={handleSearch}>
                                <Search className="h-4 w-4 mr-2" />
                                Search
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Currency List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Currencies</CardTitle>
                        <CardDescription>
                            Total: {currencies.total} currencies
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Symbol</TableHead>
                                    <TableHead>Exchange Rate</TableHead>
                                    <TableHead>Decimals</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {currencies.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                                            No currencies found
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    currencies.data.map((currency) => (
                                        <TableRow key={currency.currency_id}>
                                            <TableCell className="font-medium">{currency.code}</TableCell>
                                            <TableCell>{currency.name}</TableCell>
                                            <TableCell>{currency.symbol}</TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    {currency.formatted_rate}
                                                    {!currency.is_base && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleUpdateFromApi(currency)}
                                                        >
                                                            <RefreshCw className="h-3 w-3" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>{currency.decimal_places}</TableCell>
                                            <TableCell>
                                                {currency.is_active ? (
                                                    <Badge variant="default" className="bg-green-500">
                                                        <CheckCircle2 className="h-3 w-3 mr-1" />
                                                        Active
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary">
                                                        <AlertCircle className="h-3 w-3 mr-1" />
                                                        Inactive
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {currency.is_base ? (
                                                    <Badge variant="outline" className="border-blue-500 text-blue-500">
                                                        Base Currency
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">Regular</Badge>
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
                                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                        <DropdownMenuItem onClick={() => openHistoryDialog(currency)}>
                                                            <History className="h-4 w-4 mr-2" />
                                                            View History
                                                        </DropdownMenuItem>
                                                        {!currency.is_base && (
                                                            <DropdownMenuItem onClick={() => handleUpdateFromApi(currency)}>
                                                                <RefreshCw className="h-4 w-4 mr-2" />
                                                                Update from API
                                                            </DropdownMenuItem>
                                                        )}
                                                        <DropdownMenuSeparator />
                                                        {auth.user.role === 'admin' && (
                                                            <>
                                                                <DropdownMenuItem onClick={() => openEditDialog(currency)}>
                                                                    Edit
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => handleToggleStatus(currency)}>
                                                                    {currency.is_active ? 'Deactivate' : 'Activate'}
                                                                </DropdownMenuItem>
                                                                {!currency.is_base && (
                                                                    <DropdownMenuItem onClick={() => handleSetAsBase(currency)}>
                                                                        Set as Base
                                                                    </DropdownMenuItem>
                                                                )}
                                                                {!currency.is_base && (
                                                                    <>
                                                                        <DropdownMenuSeparator />
                                                                        <DropdownMenuItem
                                                                            onClick={() => openDeleteDialog(currency)}
                                                                            className="text-red-600"
                                                                        >
                                                                            Delete
                                                                        </DropdownMenuItem>
                                                                    </>
                                                                )}
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

                {/* Pagination */}
                {currencies.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {Array.from({ length: currencies.last_page }, (_, i) => i + 1).map((page) => (
                            <Button
                                key={page}
                                variant={page === currencies.current_page ? 'default' : 'outline'}
                                size="sm"
                                onClick={() =>
                                    router.get(
                                        route('settings.currencies.index'),
                                        { page, search: searchTerm, status: statusFilter !== 'all' ? statusFilter : undefined },
                                        { preserveState: true }
                                    )
                                }
                            >
                                {page}
                            </Button>
                        ))}
                    </div>
                )}
            </div>

            {/* Create Dialog */}
            <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add New Currency</DialogTitle>
                        <DialogDescription>Create a new currency for the payroll system</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="code">Currency Code *</Label>
                            <Input
                                id="code"
                                value={formData.code}
                                onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                                placeholder="USD"
                                maxLength={10}
                            />
                        </div>
                        <div>
                            <Label htmlFor="name">Currency Name *</Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                placeholder="US Dollar"
                            />
                        </div>
                        <div>
                            <Label htmlFor="symbol">Symbol *</Label>
                            <Input
                                id="symbol"
                                value={formData.symbol}
                                onChange={(e) => setFormData({ ...formData, symbol: e.target.value })}
                                placeholder="$"
                                maxLength={10}
                            />
                        </div>
                        <div>
                            <Label htmlFor="exchange_rate">Exchange Rate *</Label>
                            <Input
                                id="exchange_rate"
                                type="number"
                                step="0.0001"
                                value={formData.exchange_rate}
                                onChange={(e) => setFormData({ ...formData, exchange_rate: parseFloat(e.target.value) })}
                            />
                        </div>
                        <div>
                            <Label htmlFor="decimal_places">Decimal Places *</Label>
                            <Input
                                id="decimal_places"
                                type="number"
                                min="0"
                                max="4"
                                value={formData.decimal_places}
                                onChange={(e) => setFormData({ ...formData, decimal_places: parseInt(e.target.value) })}
                            />
                        </div>
                        <div>
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                placeholder="Optional description"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowCreateDialog(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleCreate} disabled={createMutation.isPending}>
                            {createMutation.isPending ? 'Creating...' : 'Create Currency'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Edit Dialog */}
            <Dialog open={showEditDialog} onOpenChange={setShowEditDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Currency</DialogTitle>
                        <DialogDescription>Update currency information</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label>Currency Code</Label>
                            <Input value={formData.code} disabled />
                        </div>
                        <div>
                            <Label htmlFor="edit-name">Currency Name *</Label>
                            <Input
                                id="edit-name"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit-symbol">Symbol *</Label>
                            <Input
                                id="edit-symbol"
                                value={formData.symbol}
                                onChange={(e) => setFormData({ ...formData, symbol: e.target.value })}
                                maxLength={10}
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit-exchange_rate">Exchange Rate *</Label>
                            <Input
                                id="edit-exchange_rate"
                                type="number"
                                step="0.0001"
                                value={formData.exchange_rate}
                                onChange={(e) => setFormData({ ...formData, exchange_rate: parseFloat(e.target.value) })}
                                disabled={store.selectedCurrency?.is_base}
                            />
                            {store.selectedCurrency?.is_base && (
                                <p className="text-xs text-muted-foreground mt-1">
                                    Base currency exchange rate is always 1.0000
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="edit-decimal_places">Decimal Places *</Label>
                            <Input
                                id="edit-decimal_places"
                                type="number"
                                min="0"
                                max="4"
                                value={formData.decimal_places}
                                onChange={(e) => setFormData({ ...formData, decimal_places: parseInt(e.target.value) })}
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit-description">Description</Label>
                            <Textarea
                                id="edit-description"
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowEditDialog(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleUpdate} disabled={updateMutation.isPending}>
                            {updateMutation.isPending ? 'Updating...' : 'Update Currency'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Currency</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete the currency "{currencyToDelete?.code}"?
                        </DialogDescription>
                    </DialogHeader>
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            This action cannot be undone. This will permanently delete the currency.
                        </AlertDescription>
                    </Alert>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowDeleteDialog(false)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={deleteMutation.isPending}>
                            {deleteMutation.isPending ? 'Deleting...' : 'Delete Currency'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* History Dialog */}
            <Dialog open={showHistoryDialog} onOpenChange={setShowHistoryDialog}>
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Exchange Rate History - {store.selectedCurrency?.code}</DialogTitle>
                        <DialogDescription>{store.selectedCurrency?.name}</DialogDescription>
                    </DialogHeader>
                    <div className="max-h-[500px] overflow-auto">
                        {loadingHistory ? (
                            <div className="text-center py-8">Loading history...</div>
                        ) : currencyHistory.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground">No history available</div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Rate</TableHead>
                                        <TableHead>Previous</TableHead>
                                        <TableHead>Change</TableHead>
                                        <TableHead>Source</TableHead>
                                        <TableHead>Updated By</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {currencyHistory.map((record) => (
                                        <TableRow key={record.history_id}>
                                            <TableCell>{record.formatted_date}</TableCell>
                                            <TableCell className="font-medium">{record.formatted_rate}</TableCell>
                                            <TableCell>{record.formatted_previous_rate || '-'}</TableCell>
                                            <TableCell>
                                                {record.change_percentage !== null && (
                                                    <div className="flex items-center gap-1">
                                                        {record.change_amount > 0 ? (
                                                            <TrendingUp className="h-4 w-4 text-green-500" />
                                                        ) : record.change_amount < 0 ? (
                                                            <TrendingDown className="h-4 w-4 text-red-500" />
                                                        ) : null}
                                                        <span className={record.change_amount > 0 ? 'text-green-500' : record.change_amount < 0 ? 'text-red-500' : ''}>
                                                            {record.formatted_change}
                                                        </span>
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{record.source_label}</Badge>
                                            </TableCell>
                                            <TableCell>{record.updated_by || 'System'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </div>
                    <DialogFooter>
                        <Button onClick={() => setShowHistoryDialog(false)}>Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
