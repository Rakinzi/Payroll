import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, Users, UserX, UserCog } from 'lucide-react';
import NoticesWidget from '@/components/notices-widget';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardStats {
    total_cost_centers?: number;
    total_employees?: number;
    total_ex_employees?: number;
    total_users?: number;
    cost_center_name?: string;
}

interface DashboardProps {
    stats: DashboardStats;
}

export default function Dashboard({ stats }: DashboardProps) {
    const { auth } = usePage<any>().props;
    const user = auth.user;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                {/* Welcome Section */}
                <div className="space-y-2">
                    <h1 className="text-3xl font-bold tracking-tight">
                        Welcome back, {user?.employee?.firstname || user?.name}!
                    </h1>
                    <p className="text-muted-foreground">
                        {user?.is_super_admin
                            ? 'You have access to all cost centers as a super administrator.'
                            : `Managing ${stats.cost_center_name || 'your cost center'}`}
                    </p>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {user?.is_super_admin && stats.total_cost_centers !== undefined && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Cost Centers</CardTitle>
                                <Building2 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.total_cost_centers}</div>
                                <p className="text-xs text-muted-foreground">Active cost centers</p>
                            </CardContent>
                        </Card>
                    )}

                    {stats.total_employees !== undefined && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Active Employees</CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.total_employees}</div>
                                <p className="text-xs text-muted-foreground">Currently employed</p>
                            </CardContent>
                        </Card>
                    )}

                    {stats.total_ex_employees !== undefined && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Ex-Employees</CardTitle>
                                <UserX className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.total_ex_employees}</div>
                                <p className="text-xs text-muted-foreground">Former employees</p>
                            </CardContent>
                        </Card>
                    )}

                    {user?.is_super_admin && stats.total_users !== undefined && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">System Users</CardTitle>
                                <UserCog className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.total_users}</div>
                                <p className="text-xs text-muted-foreground">Active user accounts</p>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Quick Actions */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {user?.can?.['view employees'] && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Employee Management</CardTitle>
                                <CardDescription>View and manage employee records</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex gap-2">
                                    <Link
                                        href="/employees"
                                        className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                                    >
                                        View Employees
                                    </Link>
                                    {user?.can?.['create employees'] && (
                                        <Link
                                            href="/employees/create"
                                            className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
                                        >
                                            Add Employee
                                        </Link>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {user?.can?.['view payroll'] && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Payroll</CardTitle>
                                <CardDescription>Process and manage payroll</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex gap-2">
                                    <Link
                                        href="/payroll"
                                        className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                                    >
                                        View Payroll
                                    </Link>
                                    {user?.can?.['process payroll'] && (
                                        <Link
                                            href="/payroll/run"
                                            className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
                                        >
                                            Run Payroll
                                        </Link>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {user?.can?.['view reports'] && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Reports</CardTitle>
                                <CardDescription>Generate and view reports</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Link
                                    href="/reports"
                                    className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                                >
                                    View Reports
                                </Link>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Notices Widget */}
                <NoticesWidget limit={5} />
            </div>
        </AppLayout>
    );
}
