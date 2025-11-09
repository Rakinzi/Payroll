import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
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
import {
    FileText,
    BarChart3,
    FileCheck,
    TrendingUp,
    Send,
    Calendar,
    Download
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    useGenerateCostAnalysis,
    useGenerateItfForm,
    useGenerateVarianceAnalysis,
    useGenerateThirdPartyReport,
} from '@/hooks/queries/use-reports';

interface Payroll {
    id: string;
    payroll_name: string;
}

interface RecentReport {
    id: string;
    type: string;
    name: string;
    payroll: string;
    generated_at: string;
}

interface ScheduledReport {
    id: string;
    report_type: string;
    payroll: {
        id: string;
        payroll_name: string;
    };
    frequency: string;
    frequency_display: string;
    next_run_at: string;
    is_global: boolean;
}

interface ReportsIndexPageProps {
    payrolls: Payroll[];
    recentReports: RecentReport[];
    scheduledReports: ScheduledReport[];
    reportTypes: Record<string, string>;
}

export default function ReportsIndex() {
    const { payrolls, recentReports, scheduledReports, reportTypes } = usePage<ReportsIndexPageProps>().props;
    const [activeTab, setActiveTab] = useState('overview');

    const generateCostAnalysisMutation = useGenerateCostAnalysis();
    const generateItfFormMutation = useGenerateItfForm();
    const generateVarianceMutation = useGenerateVarianceAnalysis();
    const generateThirdPartyMutation = useGenerateThirdPartyReport();

    const handleGenerateCostAnalysis = async () => {
        if (payrolls.length === 0) return;

        try {
            const result = await generateCostAnalysisMutation.mutateAsync({
                payroll_id: payrolls[0].id,
                report_type: 'department',
                period_start: new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
                period_end: new Date().toISOString().split('T')[0],
                currency: 'ZWG',
            });

            if (result.success && result.download_url) {
                window.open(result.download_url, '_blank');
            }
        } catch (error) {
            console.error('Failed to generate cost analysis:', error);
        }
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Reports Management
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Comprehensive payroll reporting and analysis
                    </p>
                </div>
            }
        >
            <Head title="Reports Management" />

            <div className="space-y-6">
                {/* Quick Stats */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-blue-600">
                                <FileText className="inline mr-2 h-4 w-4" />
                                Cost Analysis
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Button
                                variant="outline"
                                className="w-full"
                                onClick={handleGenerateCostAnalysis}
                                disabled={generateCostAnalysisMutation.isPending || payrolls.length === 0}
                            >
                                {generateCostAnalysisMutation.isPending ? 'Generating...' : 'Generate Report'}
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-green-600">
                                <FileCheck className="inline mr-2 h-4 w-4" />
                                Compliance
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Button variant="outline" className="w-full">
                                ITF Forms
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-orange-600">
                                <TrendingUp className="inline mr-2 h-4 w-4" />
                                Variance
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Button variant="outline" className="w-full">
                                Analyze Variance
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-purple-600">
                                <Send className="inline mr-2 h-4 w-4" />
                                Third-Party
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Button variant="outline" className="w-full">
                                Generate Report
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content */}
                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className="grid w-full grid-cols-3">
                        <TabsTrigger value="overview">
                            <BarChart3 className="mr-2 h-4 w-4" />
                            Overview
                        </TabsTrigger>
                        <TabsTrigger value="recent">
                            <FileText className="mr-2 h-4 w-4" />
                            Recent Reports
                        </TabsTrigger>
                        <TabsTrigger value="scheduled">
                            <Calendar className="mr-2 h-4 w-4" />
                            Scheduled
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <Card>
                            <CardHeader>
                                <CardTitle>Available Report Types</CardTitle>
                                <CardDescription>
                                    Select a report type to generate comprehensive payroll analytics
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {Object.entries(reportTypes).map(([key, label]) => (
                                        <Card key={key}>
                                            <CardHeader>
                                                <CardTitle className="text-base">{label}</CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <Button variant="outline" className="w-full">
                                                    Generate
                                                </Button>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="recent">
                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Reports</CardTitle>
                                <CardDescription>
                                    Recently generated reports available for download
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {recentReports.length === 0 ? (
                                    <div className="text-center py-8 text-muted-foreground">
                                        No recent reports available
                                    </div>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Report Name</TableHead>
                                                <TableHead>Payroll</TableHead>
                                                <TableHead>Generated</TableHead>
                                                <TableHead className="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {recentReports.map((report) => (
                                                <TableRow key={report.id}>
                                                    <TableCell className="font-medium">{report.name}</TableCell>
                                                    <TableCell>{report.payroll}</TableCell>
                                                    <TableCell>
                                                        {new Date(report.generated_at).toLocaleString()}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <Button variant="ghost" size="sm">
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="scheduled">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Scheduled Reports</CardTitle>
                                        <CardDescription>
                                            Automated reports that run on a schedule
                                        </CardDescription>
                                    </div>
                                    <Button>
                                        <Calendar className="mr-2 h-4 w-4" />
                                        New Schedule
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {scheduledReports.length === 0 ? (
                                    <div className="text-center py-8 text-muted-foreground">
                                        No scheduled reports configured
                                    </div>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Report Type</TableHead>
                                                <TableHead>Payroll</TableHead>
                                                <TableHead>Frequency</TableHead>
                                                <TableHead>Next Run</TableHead>
                                                <TableHead className="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {scheduledReports.map((schedule) => (
                                                <TableRow key={schedule.id}>
                                                    <TableCell>
                                                        {schedule.report_type}
                                                        {schedule.is_global && (
                                                            <Badge variant="secondary" className="ml-2">
                                                                Global
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>{schedule.payroll.payroll_name}</TableCell>
                                                    <TableCell>
                                                        <Badge variant="outline">
                                                            {schedule.frequency_display}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        {schedule.next_run_at
                                                            ? new Date(schedule.next_run_at).toLocaleString()
                                                            : 'N/A'}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <Button variant="ghost" size="sm">
                                                            Edit
                                                        </Button>
                                                    </TableCell>
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
