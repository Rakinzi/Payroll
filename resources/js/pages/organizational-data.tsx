import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useOrganizationalData } from '@/hooks/queries/use-organizational-data';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Building, Briefcase, DollarSign, GraduationCap, MapPin, FileText, Users, TrendingUp } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Organizational Data', href: '/organizational-data' },
];

export default function OrganizationalDataPage() {
    const { data, isLoading, error } = useOrganizationalData();

    if (isLoading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Organizational Data" />
                <div className="flex h-full items-center justify-center p-6">
                    <Spinner className="h-8 w-8" />
                </div>
            </AppLayout>
        );
    }

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Organizational Data" />
                <div className="p-6">
                    <Card className="border-destructive">
                        <CardContent className="pt-6">
                            <p className="text-destructive">
                                Error: {error instanceof Error ? error.message : 'Failed to load data'}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organizational Data" />
            <div className="flex flex-col gap-6 p-6">
                <div className="space-y-2">
                    <h1 className="text-3xl font-bold tracking-tight">Organizational Data Management</h1>
                    <p className="text-muted-foreground">
                        Manage company structure, payroll codes, tax bands, and other organizational configurations
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Departments</CardTitle>
                            <Building className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{data?.departments.length ?? 0}</div>
                            <p className="text-xs text-muted-foreground">Active departments</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Positions</CardTitle>
                            <Briefcase className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{data?.positions.length ?? 0}</div>
                            <p className="text-xs text-muted-foreground">Job positions</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Transaction Codes</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{data?.transaction_codes.length ?? 0}</div>
                            <p className="text-xs text-muted-foreground">Payroll codes</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Tax Bands</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{data?.tax_bands.length ?? 0}</div>
                            <p className="text-xs text-muted-foreground">Tax brackets</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    {/* Departments */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building className="h-5 w-5" />
                                Departments
                            </CardTitle>
                            <CardDescription>Organizational departments and divisions</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {data?.departments.slice(0, 5).map((dept) => (
                                    <div
                                        key={dept.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div>
                                            <p className="font-medium">{dept.dept_name}</p>
                                            {dept.description && (
                                                <p className="text-sm text-muted-foreground">
                                                    {dept.description}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                {(data?.departments.length ?? 0) > 5 && (
                                    <p className="text-sm text-muted-foreground">
                                        And {(data?.departments.length ?? 0) - 5} more...
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Positions */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Briefcase className="h-5 w-5" />
                                Positions
                            </CardTitle>
                            <CardDescription>Job positions and designations</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {data?.positions.slice(0, 5).map((position) => (
                                    <div
                                        key={position.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div>
                                            <p className="font-medium">{position.position_name}</p>
                                            {position.description && (
                                                <p className="text-sm text-muted-foreground">
                                                    {position.description}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                {(data?.positions.length ?? 0) > 5 && (
                                    <p className="text-sm text-muted-foreground">
                                        And {(data?.positions.length ?? 0) - 5} more...
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Transaction Codes */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <DollarSign className="h-5 w-5" />
                                Transaction Codes
                            </CardTitle>
                            <CardDescription>Earnings, deductions, and contributions</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {data?.transaction_codes.slice(0, 5).map((code) => (
                                    <div
                                        key={code.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {code.code_number} - {code.code_name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {code.code_category}
                                                {code.is_benefit && ' • Employee Benefit'}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                {(data?.transaction_codes.length ?? 0) > 5 && (
                                    <p className="text-sm text-muted-foreground">
                                        And {(data?.transaction_codes.length ?? 0) - 5} more...
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* NEC Grades */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <GraduationCap className="h-5 w-5" />
                                NEC Grades
                            </CardTitle>
                            <CardDescription>Employment council grading system</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {data?.nec_grades.slice(0, 5).map((grade) => (
                                    <div
                                        key={grade.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div>
                                            <p className="font-medium">{grade.grade_name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {grade.contribution} contribution
                                                {grade.is_automatic && ' • Automatic'}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                {(data?.nec_grades.length ?? 0) > 5 && (
                                    <p className="text-sm text-muted-foreground">
                                        And {(data?.nec_grades.length ?? 0) - 5} more...
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tax Bands */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5" />
                                Tax Bands
                            </CardTitle>
                            <CardDescription>Progressive tax brackets</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {data?.tax_bands.slice(0, 5).map((band) => (
                                    <div
                                        key={band.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {band.currency} - {band.period}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                ${band.min_salary.toLocaleString()} - $
                                                {band.max_salary?.toLocaleString() ?? '∞'} at {band.tax_rate}%
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                {(data?.tax_bands.length ?? 0) > 5 && (
                                    <p className="text-sm text-muted-foreground">
                                        And {(data?.tax_bands.length ?? 0) - 5} more...
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Supporting Data */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Supporting Data
                            </CardTitle>
                            <CardDescription>Industries, occupations, and paypoints</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Industries</span>
                                    <span className="text-sm text-muted-foreground">
                                        {data?.industries.length ?? 0}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Occupations</span>
                                    <span className="text-sm text-muted-foreground">
                                        {data?.occupations.length ?? 0}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Paypoints</span>
                                    <span className="text-sm text-muted-foreground">
                                        {data?.paypoints.length ?? 0}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
