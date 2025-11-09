import { AccountingPeriodTable } from '@/components/accounting-period-table';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import Heading from '@/components/heading';
import type { AccountingPeriod } from '@/hooks/queries/use-accounting-periods';
import { useGeneratePeriods } from '@/hooks/queries/use-accounting-periods';
import { useAccountingPeriodStore } from '@/stores/accounting-period-store';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CalendarPlus, Filter } from 'lucide-react';
import { useState } from 'react';

interface Props {
    periods: {
        data: AccountingPeriod[];
        links: any[];
        meta: any;
    };
    payrolls: Array<{
        id: string;
        name: string;
        period_type: string;
        created_at: string;
    }>;
    currentPayrollId: string;
    years: number[];
    currentYear: number;
    costCenters: any[];
    userCenterId: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Accounting Periods', href: '/accounting-periods' },
];

export default function AccountingPeriodsIndex({
    periods,
    payrolls,
    currentPayrollId,
    years,
    currentYear,
    costCenters,
    userCenterId,
}: Props) {
    const [showGenerateDialog, setShowGenerateDialog] = useState(false);
    const [generateForm, setGenerateForm] = useState({
        payroll_id: currentPayrollId || '',
        year: currentYear,
    });

    const isAdmin = userCenterId === '0' || costCenters.length > 1;

    // Generate periods mutation
    const generatePeriods = useGeneratePeriods();

    const handlePayrollChange = (payrollId: string) => {
        router.get(
            '/accounting-periods',
            { payroll_id: payrollId, year: currentYear },
            { preserveState: false, replace: true }
        );
    };

    const handleYearChange = (year: string) => {
        router.get(
            '/accounting-periods',
            { payroll_id: currentPayrollId, year: parseInt(year) },
            { preserveState: false, replace: true }
        );
    };

    const handleGeneratePeriods = () => {
        if (!generateForm.payroll_id || !generateForm.year) {
            return;
        }

        generatePeriods.mutate(generateForm, {
            onSuccess: () => {
                setShowGenerateDialog(false);
                setGenerateForm({
                    payroll_id: currentPayrollId || '',
                    year: currentYear,
                });
            },
        });
    };

    const selectedPayroll = payrolls.find((p) => p.id === currentPayrollId);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Accounting Periods" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <Heading>Accounting Periods</Heading>
                        <p className="text-muted-foreground mt-1">
                            Manage payroll accounting periods and process payroll runs
                        </p>
                    </div>
                    {isAdmin && (
                        <Button onClick={() => setShowGenerateDialog(true)}>
                            <CalendarPlus className="mr-2 h-4 w-4" />
                            Generate Periods
                        </Button>
                    )}
                </div>

                {/* Filters Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <Filter className="h-5 w-5" />
                                    Filters
                                </CardTitle>
                                <CardDescription>
                                    Select payroll and year to view accounting periods
                                </CardDescription>
                            </div>
                            {selectedPayroll && (
                                <Badge variant="outline" className="text-sm">
                                    {selectedPayroll.period_type}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="payroll">Payroll</Label>
                                <Select
                                    value={currentPayrollId}
                                    onValueChange={handlePayrollChange}
                                >
                                    <SelectTrigger id="payroll">
                                        <SelectValue placeholder="Select payroll" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {payrolls.map((payroll) => (
                                            <SelectItem key={payroll.id} value={payroll.id}>
                                                {payroll.name} - {payroll.created_at}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="year">Year</Label>
                                <Select
                                    value={currentYear.toString()}
                                    onValueChange={handleYearChange}
                                >
                                    <SelectTrigger id="year">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {years.map((year) => (
                                            <SelectItem key={year} value={year.toString()}>
                                                {year}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Periods Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Payroll Periods</CardTitle>
                        <CardDescription>
                            {isAdmin
                                ? 'View and monitor payroll periods across all cost centers'
                                : 'Manage payroll periods for your cost center'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AccountingPeriodTable
                            periods={periods.data}
                            userCenterId={userCenterId}
                            isAdmin={isAdmin}
                        />

                        {/* Pagination */}
                        {periods.meta && periods.meta.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Showing {periods.meta.from} to {periods.meta.to} of{' '}
                                    {periods.meta.total} periods
                                </p>
                                <div className="flex gap-2">
                                    {periods.links.map((link: any, index: number) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => {
                                                if (link.url) {
                                                    router.get(link.url);
                                                }
                                            }}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Information Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>About Accounting Periods</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            <strong>Run Period:</strong> Processes payroll for all active employees
                            in your cost center, calculating salaries, taxes, and deductions.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            <strong>Re-Calculate:</strong> Updates existing payslips with the latest
                            employee data, transaction codes, and tax rates.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            <strong>Close Period:</strong> Finalizes the period and marks all
                            payslips as ready for distribution. Once closed, payslips cannot be
                            modified.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            <strong>Currency:</strong> Select the currency mode for the period
                            (Multi-currency, USD only, or ZWG only). This must be set before running
                            the period.
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Generate Periods Dialog */}
            <Dialog open={showGenerateDialog} onOpenChange={setShowGenerateDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Generate Accounting Periods</DialogTitle>
                        <DialogDescription>
                            Create monthly accounting periods for a specific payroll and year. This
                            will generate 12 periods (January through December).
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="generate-payroll">Payroll</Label>
                            <Select
                                value={generateForm.payroll_id}
                                onValueChange={(value) =>
                                    setGenerateForm((prev) => ({ ...prev, payroll_id: value }))
                                }
                            >
                                <SelectTrigger id="generate-payroll">
                                    <SelectValue placeholder="Select payroll" />
                                </SelectTrigger>
                                <SelectContent>
                                    {payrolls.map((payroll) => (
                                        <SelectItem key={payroll.id} value={payroll.id}>
                                            {payroll.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="generate-year">Year</Label>
                            <Input
                                id="generate-year"
                                type="number"
                                min="2020"
                                max="2100"
                                value={generateForm.year}
                                onChange={(e) =>
                                    setGenerateForm((prev) => ({
                                        ...prev,
                                        year: parseInt(e.target.value) || currentYear,
                                    }))
                                }
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowGenerateDialog(false)}
                            disabled={generatePeriods.isPending}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleGeneratePeriods}
                            disabled={
                                generatePeriods.isPending ||
                                !generateForm.payroll_id ||
                                !generateForm.year
                            }
                        >
                            {generatePeriods.isPending ? 'Generating...' : 'Generate Periods'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
