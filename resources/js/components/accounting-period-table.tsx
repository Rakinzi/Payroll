import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    type AccountingPeriod,
    useClosePeriod,
    useRefreshPeriod,
    useRunPeriod,
    useUpdatePeriodCurrency,
} from '@/hooks/queries/use-accounting-periods';
import { useAccountingPeriodStore } from '@/stores/accounting-period-store';
import { format } from 'date-fns';
import { AlertCircle, CheckCircle2, Clock, PlayCircle, RefreshCw, XCircle } from 'lucide-react';
import { useEffect } from 'react';
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

interface AccountingPeriodTableProps {
    periods: AccountingPeriod[];
    userCenterId: string;
    isAdmin: boolean;
}

export function AccountingPeriodTable({
    periods,
    userCenterId,
    isAdmin,
}: AccountingPeriodTableProps) {
    const {
        processingPeriods,
        startProcessing,
        stopProcessing,
        isProcessing,
        periodCurrencies,
        setPeriodCurrency,
        getPeriodCurrency,
        confirmationDialog,
        openConfirmationDialog,
        closeConfirmationDialog,
    } = useAccountingPeriodStore();

    // Get status badge component
    const getStatusBadge = (period: AccountingPeriod) => {
        if (period.is_current) {
            return (
                <Badge variant="default" className="bg-yellow-500">
                    <Clock className="mr-1 h-3 w-3" />
                    Current
                </Badge>
            );
        } else if (period.is_future) {
            return (
                <Badge variant="secondary">
                    <AlertCircle className="mr-1 h-3 w-3" />
                    Future
                </Badge>
            );
        } else {
            return (
                <Badge variant="outline">
                    <CheckCircle2 className="mr-1 h-3 w-3" />
                    Past
                </Badge>
            );
        }
    };

    // Get center status for current user
    const getCenterStatus = (period: AccountingPeriod) => {
        if (isAdmin) {
            return null; // Admin sees all centers
        }
        return period.center_statuses?.find((s) => s.center_id === userCenterId);
    };

    // Get action buttons for a period
    const getActionButtons = (period: AccountingPeriod) => {
        const centerStatus = getCenterStatus(period);
        const currency = getPeriodCurrency(period.period_id);
        const processing = isProcessing(period.period_id);

        if (processing) {
            return (
                <div className="flex items-center gap-2">
                    <Spinner className="h-4 w-4" />
                    <span className="text-sm text-muted-foreground">Processing...</span>
                </div>
            );
        }

        // Admin view - show completion status
        if (isAdmin) {
            return (
                <div className="flex items-center gap-2">
                    <span className="text-sm font-medium">
                        {period.completion_percentage}% Complete
                    </span>
                    <Badge variant={period.completion_percentage === 100 ? 'default' : 'secondary'}>
                        {period.center_statuses?.filter((s) => s.period_run_date).length || 0} /{' '}
                        {period.center_statuses?.length || 0} Centers
                    </Badge>
                </div>
            );
        }

        // User view - show action buttons
        if (!centerStatus) {
            return <Badge variant="outline">No Status</Badge>;
        }

        if (centerStatus.can_be_run) {
            return (
                <Button
                    size="sm"
                    onClick={() =>
                        openConfirmationDialog(period.period_id, 'run', currency)
                    }
                    disabled={!period.is_current && !period.is_past}
                >
                    <PlayCircle className="mr-2 h-4 w-4" />
                    Run Period
                </Button>
            );
        } else if (centerStatus.can_be_refreshed) {
            return (
                <div className="flex gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() =>
                            openConfirmationDialog(period.period_id, 'refresh', currency)
                        }
                    >
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Re-Calculate
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        onClick={() =>
                            openConfirmationDialog(period.period_id, 'close')
                        }
                    >
                        <XCircle className="mr-2 h-4 w-4" />
                        Close Period
                    </Button>
                </div>
            );
        } else if (centerStatus.is_completed) {
            return (
                <Badge variant="default" className="bg-green-600">
                    <CheckCircle2 className="mr-1 h-3 w-3" />
                    Completed
                </Badge>
            );
        }

        return <Badge variant="outline">Pending</Badge>;
    };

    // Handle currency change
    const { mutate: updateCurrency } = useUpdatePeriodCurrency(0);

    const handleCurrencyChange = (periodId: number, currency: 'ZWL' | 'USD' | 'DEFAULT') => {
        setPeriodCurrency(periodId, currency);
        updateCurrency(
            { currency },
            {
                onError: () => {
                    // Revert on error
                    setPeriodCurrency(periodId, 'DEFAULT');
                },
            }
        );
    };

    // Mutation hooks
    const runPeriodMutation = useRunPeriod(
        confirmationDialog.periodId || 0
    );
    const refreshPeriodMutation = useRefreshPeriod(
        confirmationDialog.periodId || 0
    );
    const closePeriodMutation = useClosePeriod(
        confirmationDialog.periodId || 0
    );

    // Handle confirmation
    const handleConfirm = () => {
        if (!confirmationDialog.periodId || !confirmationDialog.action) return;

        const periodId = confirmationDialog.periodId;
        const action = confirmationDialog.action;

        startProcessing(periodId, action);

        if (action === 'run' && confirmationDialog.currency) {
            runPeriodMutation.mutate(
                { currency: confirmationDialog.currency },
                {
                    onSettled: () => stopProcessing(periodId),
                }
            );
        } else if (action === 'refresh' && confirmationDialog.currency) {
            refreshPeriodMutation.mutate(
                { currency: confirmationDialog.currency },
                {
                    onSettled: () => stopProcessing(periodId),
                }
            );
        } else if (action === 'close') {
            closePeriodMutation.mutate(undefined, {
                onSettled: () => stopProcessing(periodId),
            });
        }

        closeConfirmationDialog();
    };

    // Initialize currency selections
    useEffect(() => {
        periods.forEach((period) => {
            const centerStatus = getCenterStatus(period);
            if (centerStatus && !periodCurrencies.has(period.period_id)) {
                setPeriodCurrency(period.period_id, centerStatus.period_currency);
            }
        });
    }, [periods]);

    return (
        <>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-primary/5">
                            <TableHead className="font-semibold">#</TableHead>
                            <TableHead className="font-semibold">Month</TableHead>
                            <TableHead className="font-semibold">Period Start</TableHead>
                            <TableHead className="font-semibold">Period End</TableHead>
                            {!isAdmin && (
                                <>
                                    <TableHead className="font-semibold">Run Date</TableHead>
                                    <TableHead className="font-semibold">Pay Date</TableHead>
                                </>
                            )}
                            <TableHead className="font-semibold">Currency</TableHead>
                            <TableHead className="font-semibold">Status</TableHead>
                            <TableHead className="font-semibold">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {periods.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={9} className="text-center py-8">
                                    <p className="text-muted-foreground">
                                        No accounting periods found
                                    </p>
                                </TableCell>
                            </TableRow>
                        ) : (
                            periods.map((period, index) => {
                                const centerStatus = getCenterStatus(period);
                                const rowClass = period.is_current ? 'bg-yellow-50' : '';

                                return (
                                    <TableRow key={period.period_id} className={rowClass}>
                                        <TableCell>{index + 1}</TableCell>
                                        <TableCell className="font-medium">
                                            {period.month_name}
                                        </TableCell>
                                        <TableCell>
                                            {format(new Date(period.period_start), 'MMM dd, yyyy')}
                                        </TableCell>
                                        <TableCell>
                                            {format(new Date(period.period_end), 'MMM dd, yyyy')}
                                        </TableCell>
                                        {!isAdmin && (
                                            <>
                                                <TableCell>
                                                    {centerStatus?.period_run_date
                                                        ? format(
                                                              new Date(centerStatus.period_run_date),
                                                              'MMM dd, yyyy HH:mm'
                                                          )
                                                        : 'NULL'}
                                                </TableCell>
                                                <TableCell>
                                                    {centerStatus?.pay_run_date
                                                        ? format(
                                                              new Date(centerStatus.pay_run_date),
                                                              'MMM dd, yyyy HH:mm'
                                                          )
                                                        : 'NULL'}
                                                </TableCell>
                                            </>
                                        )}
                                        <TableCell>
                                            <Select
                                                value={getPeriodCurrency(period.period_id)}
                                                onValueChange={(value: any) =>
                                                    handleCurrencyChange(period.period_id, value)
                                                }
                                                disabled={
                                                    isAdmin ||
                                                    centerStatus?.period_run_date !== null
                                                }
                                            >
                                                <SelectTrigger className="w-32">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="DEFAULT">Multi</SelectItem>
                                                    <SelectItem value="USD">USD</SelectItem>
                                                    <SelectItem value="ZWL">ZWG</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell>{getStatusBadge(period)}</TableCell>
                                        <TableCell>{getActionButtons(period)}</TableCell>
                                    </TableRow>
                                );
                            })
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Confirmation Dialog */}
            <AlertDialog
                open={confirmationDialog.isOpen}
                onOpenChange={(open) => !open && closeConfirmationDialog()}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Confirm Action</AlertDialogTitle>
                        <AlertDialogDescription>
                            {confirmationDialog.action === 'run' &&
                                'Are you sure you want to run this payroll period? This will process all active employees in your center.'}
                            {confirmationDialog.action === 'refresh' &&
                                'Are you sure you want to recalculate this payroll period? This will update all payslips with the latest data.'}
                            {confirmationDialog.action === 'close' &&
                                'Are you sure you want to close this payroll period? This action will finalize all payslips and they will be ready for distribution. This action cannot be easily undone.'}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleConfirm}>
                            Continue
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
