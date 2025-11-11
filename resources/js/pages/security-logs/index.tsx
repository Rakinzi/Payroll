import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type PaginatedData, type PaginationLink } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Shield, AlertTriangle, Activity, Clock } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Security Logs', href: '/security-logs' },
];

interface User {
    id: string;
    name: string;
    email: string;
}

interface SecurityLog {
    id: string;
    user_id: string | null;
    user?: User;
    event: string;
    severity: string;
    details: Record<string, any> | null;
    ip_address: string;
    user_agent: string;
    created_at: string;
}

interface Statistics {
    total_events: number;
    high_severity_count: number;
    today_events: number;
    failed_logins_today: number;
}

interface Props {
    securityLogs: PaginatedData<SecurityLog>;
    severityLevels: string[];
    eventTypes: string[];
    statistics: Statistics;
    filters?: {
        search?: string;
        severity?: string;
        event?: string;
        high_severity?: boolean;
        start_date?: string;
        end_date?: string;
    };
}

export default function SecurityLogsIndex({
    securityLogs,
    severityLevels,
    eventTypes,
    statistics,
    filters
}: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [selectedSeverity, setSelectedSeverity] = useState(filters?.severity ?? 'all');
    const [selectedEvent, setSelectedEvent] = useState(filters?.event ?? 'all');

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(
            '/security-logs',
            { ...filters, search: value },
            { preserveState: true, replace: true }
        );
    };

    const handleSeverityFilter = (severity: string) => {
        setSelectedSeverity(severity);
        router.get(
            '/security-logs',
            { ...filters, severity: severity === 'all' ? undefined : severity },
            { preserveState: true, replace: true }
        );
    };

    const handleEventFilter = (event: string) => {
        setSelectedEvent(event);
        router.get(
            '/security-logs',
            { ...filters, event: event === 'all' ? undefined : event },
            { preserveState: true, replace: true }
        );
    };

    const getSeverityBadgeVariant = (severity: string) => {
        switch (severity) {
            case 'low':
                return 'secondary';
            case 'medium':
                return 'default';
            case 'high':
                return 'destructive';
            case 'critical':
                return 'destructive';
            default:
                return 'secondary';
        }
    };

    const formatEventName = (event: string) => {
        return event
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Security Logs" />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                            <Shield className="h-8 w-8" />
                            Security Logs
                        </h1>
                        <p className="text-muted-foreground">
                            Monitor security events and potential threats
                        </p>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Events</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.total_events}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">High Severity</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.high_severity_count}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Today's Events</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.today_events}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Failed Logins Today</CardTitle>
                            <Shield className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.failed_logins_today}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Search & Filters */}
                <div className="flex flex-wrap gap-4">
                    <Input
                        placeholder="Search events or IP addresses..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="max-w-sm"
                    />
                    <Select value={selectedSeverity} onValueChange={handleSeverityFilter}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="All Severities" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Severities</SelectItem>
                            {severityLevels.map((severity) => (
                                <SelectItem key={severity} value={severity}>
                                    {severity.charAt(0).toUpperCase() + severity.slice(1)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={selectedEvent} onValueChange={handleEventFilter}>
                        <SelectTrigger className="w-[250px]">
                            <SelectValue placeholder="All Events" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Events</SelectItem>
                            {eventTypes.map((event) => (
                                <SelectItem key={event} value={event}>
                                    {formatEventName(event)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Security Logs Table */}
                <Card>
                    <CardContent className="pt-6">
                        {securityLogs.data.length === 0 ? (
                            <div className="flex items-center justify-center py-12">
                                <div className="text-center">
                                    <Shield className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                    <p className="text-muted-foreground">No security logs found</p>
                                </div>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Timestamp</TableHead>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Severity</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>IP Address</TableHead>
                                        <TableHead>Details</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {securityLogs.data.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell className="font-medium">
                                                {formatDate(log.created_at)}
                                            </TableCell>
                                            <TableCell>
                                                {formatEventName(log.event)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getSeverityBadgeVariant(log.severity)}>
                                                    {log.severity}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {log.user?.name || 'Unknown'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground font-mono text-sm">
                                                {log.ip_address}
                                            </TableCell>
                                            <TableCell className="max-w-md">
                                                {log.details && (
                                                    <span className="text-sm text-muted-foreground">
                                                        {JSON.stringify(log.details)}
                                                    </span>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {securityLogs.links && securityLogs.links.length > 3 && (
                    <div className="flex justify-center gap-2">
                        {securityLogs.links.map((link: PaginationLink, index: number) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
