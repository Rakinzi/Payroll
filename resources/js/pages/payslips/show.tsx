import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ArrowLeft, Download, Eye, Mail, Check, FileText, DollarSign } from 'lucide-react';
import type { Payslip } from '@/hooks/queries/use-payslips';

interface PayslipShowPageProps {
    payslip: Payslip;
}

export default function PayslipShow() {
    const { payslip } = usePage<PayslipShowPageProps>().props;

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'draft':
                return 'secondary';
            case 'finalized':
                return 'default';
            case 'distributed':
                return 'default';
            case 'cancelled':
                return 'destructive';
            default:
                return 'secondary';
        }
    };

    const earnings = payslip.transactions?.filter((t) => t.transaction_type === 'earning') || [];
    const deductions = payslip.transactions?.filter((t) => t.transaction_type === 'deduction') || [];

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/payslips">
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800">
                                Payslip Details
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {payslip.payslip_number} - {payslip.period_display}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge variant={getStatusColor(payslip.status)}>
                            {payslip.status_display}
                        </Badge>
                    </div>
                </div>
            }
        >
            <Head title={`Payslip - ${payslip.payslip_number}`} />

            <div className="space-y-6">
                {/* Employee & Payroll Info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Employee Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Employee ID
                                </div>
                                <div className="text-base font-semibold">
                                    {payslip.employee.emp_system_id}
                                </div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Employee Name
                                </div>
                                <div className="text-base font-semibold">
                                    {payslip.employee.full_name}
                                </div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Position
                                </div>
                                <div className="text-base font-semibold">
                                    {payslip.employee.position?.position_name || 'N/A'}
                                </div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Department
                                </div>
                                <div className="text-base font-semibold">
                                    {payslip.employee.department?.department_name || 'N/A'}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabs for different views */}
                <Tabs defaultValue="preview" className="w-full">
                    <TabsList className="grid w-full grid-cols-3">
                        <TabsTrigger value="preview">
                            <Eye className="mr-2 h-4 w-4" />
                            PDF Preview
                        </TabsTrigger>
                        <TabsTrigger value="details">
                            <FileText className="mr-2 h-4 w-4" />
                            Details
                        </TabsTrigger>
                        <TabsTrigger value="distribution">
                            <Mail className="mr-2 h-4 w-4" />
                            Distribution
                        </TabsTrigger>
                    </TabsList>

                    {/* PDF Preview Tab */}
                    <TabsContent value="preview">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Payslip Preview</CardTitle>
                                        <CardDescription>
                                            View the PDF preview of this payslip
                                        </CardDescription>
                                    </div>
                                    <div className="flex gap-2">
                                        <a
                                            href={`/payslips/${payslip.id}/download`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <Button variant="outline">
                                                <Download className="mr-2 h-4 w-4" />
                                                Download PDF
                                            </Button>
                                        </a>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="border rounded-lg overflow-hidden">
                                    <iframe
                                        src={`/payslips/${payslip.id}/preview`}
                                        className="w-full h-[800px]"
                                        title="Payslip PDF Preview"
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Details Tab */}
                    <TabsContent value="details">
                        <div className="space-y-4">
                            {/* Summary Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-sm font-medium text-green-600">
                                            Gross Salary (ZWG)
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-green-600">
                                            {typeof payslip.gross_salary_zwg === 'string'
                                                ? payslip.gross_salary_zwg
                                                : payslip.gross_salary_zwg.toLocaleString('en-US', {
                                                      minimumFractionDigits: 2,
                                                      maximumFractionDigits: 2,
                                                  })}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-sm font-medium text-green-600">
                                            Gross Salary (USD)
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-green-600">
                                            {typeof payslip.gross_salary_usd === 'string'
                                                ? payslip.gross_salary_usd
                                                : payslip.gross_salary_usd.toLocaleString('en-US', {
                                                      minimumFractionDigits: 2,
                                                      maximumFractionDigits: 2,
                                                  })}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-sm font-medium text-blue-600">
                                            Net Pay (ZWG)
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-blue-600">
                                            {typeof payslip.net_salary_zwg === 'string'
                                                ? payslip.net_salary_zwg
                                                : payslip.net_salary_zwg.toLocaleString('en-US', {
                                                      minimumFractionDigits: 2,
                                                      maximumFractionDigits: 2,
                                                  })}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-sm font-medium text-blue-600">
                                            Net Pay (USD)
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-blue-600">
                                            {typeof payslip.net_salary_usd === 'string'
                                                ? payslip.net_salary_usd
                                                : payslip.net_salary_usd.toLocaleString('en-US', {
                                                      minimumFractionDigits: 2,
                                                      maximumFractionDigits: 2,
                                                  })}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Transactions */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {/* Earnings */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-green-600">Earnings</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Description</TableHead>
                                                    <TableHead className="text-right">ZWG</TableHead>
                                                    <TableHead className="text-right">USD</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {earnings.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell
                                                            colSpan={3}
                                                            className="text-center text-muted-foreground"
                                                        >
                                                            No earnings
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    earnings.map((earning) => (
                                                        <TableRow key={earning.id}>
                                                            <TableCell>{earning.description}</TableCell>
                                                            <TableCell className="text-right font-mono">
                                                                {earning.amount_zwg.toLocaleString('en-US', {
                                                                    minimumFractionDigits: 2,
                                                                    maximumFractionDigits: 2,
                                                                })}
                                                            </TableCell>
                                                            <TableCell className="text-right font-mono">
                                                                {earning.amount_usd.toLocaleString('en-US', {
                                                                    minimumFractionDigits: 2,
                                                                    maximumFractionDigits: 2,
                                                                })}
                                                            </TableCell>
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </CardContent>
                                </Card>

                                {/* Deductions */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-red-600">Deductions</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Description</TableHead>
                                                    <TableHead className="text-right">ZWG</TableHead>
                                                    <TableHead className="text-right">USD</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {deductions.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell
                                                            colSpan={3}
                                                            className="text-center text-muted-foreground"
                                                        >
                                                            No deductions
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    deductions.map((deduction) => (
                                                        <TableRow key={deduction.id}>
                                                            <TableCell>{deduction.description}</TableCell>
                                                            <TableCell className="text-right font-mono">
                                                                {deduction.amount_zwg.toLocaleString('en-US', {
                                                                    minimumFractionDigits: 2,
                                                                    maximumFractionDigits: 2,
                                                                })}
                                                            </TableCell>
                                                            <TableCell className="text-right font-mono">
                                                                {deduction.amount_usd.toLocaleString('en-US', {
                                                                    minimumFractionDigits: 2,
                                                                    maximumFractionDigits: 2,
                                                                })}
                                                            </TableCell>
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </TabsContent>

                    {/* Distribution Tab */}
                    <TabsContent value="distribution">
                        <Card>
                            <CardHeader>
                                <CardTitle>Distribution History</CardTitle>
                                <CardDescription>
                                    Email distribution logs for this payslip
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {!payslip.distribution_logs || payslip.distribution_logs.length === 0 ? (
                                    <div className="text-center py-8 text-muted-foreground">
                                        This payslip has not been distributed yet
                                    </div>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Recipient</TableHead>
                                                <TableHead>Email</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Sent At</TableHead>
                                                <TableHead>Sent By</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {payslip.distribution_logs.map((log) => (
                                                <TableRow key={log.id}>
                                                    <TableCell>{log.recipient_name}</TableCell>
                                                    <TableCell>{log.recipient_email}</TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                log.status === 'sent' ? 'default' : 'destructive'
                                                            }
                                                        >
                                                            {log.status_display}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        {log.sent_at
                                                            ? new Date(log.sent_at).toLocaleString()
                                                            : '-'}
                                                    </TableCell>
                                                    <TableCell>{log.sender.name}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
