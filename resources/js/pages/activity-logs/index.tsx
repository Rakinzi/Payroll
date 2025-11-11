import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { Activity, Filter } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Activity Logs', href: '/activity-logs' },
];

interface User {
    id: string;
    name: string;
    email: string;
}

interface ActivityLog {
    id: string;
    user_id: string;
    user?: User;
    action: string;
    description: string;
    model_type: string | null;
    model_id: string | null;
    metadata: Record<string, any> | null;
    ip_address: string;
    user_agent: string;
    created_at: string;
}

interface Props {
    activityLogs: PaginatedData<ActivityLog>;
    actionTypes: string[];
    filters?: {
        search?: string;
        action?: string;
        user_id?: string;
        start_date?: string;
        end_date?: string;
    };
}

export default function ActivityLogsIndex({ activityLogs, actionTypes, filters }: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [selectedAction, setSelectedAction] = useState(filters?.action ?? 'all');

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(
            '/activity-logs',
            { ...filters, search: value },
            { preserveState: true, replace: true }
        );
    };

    const handleActionFilter = (action: string) => {
        setSelectedAction(action);
        router.get(
            '/activity-logs',
            { ...filters, action: action === 'all' ? undefined : action },
            { preserveState: true, replace: true }
        );
    };

    const getActionBadgeVariant = (action: string) => {
        switch (action) {
            case 'create':
                return 'default';
            case 'update':
                return 'secondary';
            case 'delete':
                return 'destructive';
            case 'login':
            case 'logout':
                return 'outline';
            case 'export':
            case 'import':
                return 'secondary';
            default:
                return 'secondary';
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Activity Logs" />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                            <Activity className="h-8 w-8" />
                            Activity Logs
                        </h1>
                        <p className="text-muted-foreground">
                            Track all user activities and system events
                        </p>
                    </div>
                </div>

                {/* Search & Filters */}
                <div className="flex gap-4">
                    <Input
                        placeholder="Search activities..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="max-w-sm"
                    />
                    <Select value={selectedAction} onValueChange={handleActionFilter}>
                        <SelectTrigger className="w-[200px]">
                            <Filter className="mr-2 h-4 w-4" />
                            <SelectValue placeholder="All Actions" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Actions</SelectItem>
                            {actionTypes.map((action) => (
                                <SelectItem key={action} value={action}>
                                    {action.charAt(0).toUpperCase() + action.slice(1)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Activity Table */}
                <Card>
                    <CardContent className="pt-6">
                        {activityLogs.data.length === 0 ? (
                            <div className="flex items-center justify-center py-12">
                                <div className="text-center">
                                    <Activity className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                    <p className="text-muted-foreground">No activity logs found</p>
                                </div>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Timestamp</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Action</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>IP Address</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activityLogs.data.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell className="font-medium">
                                                {formatDate(log.created_at)}
                                            </TableCell>
                                            <TableCell>
                                                {log.user?.name || 'System'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getActionBadgeVariant(log.action)}>
                                                    {log.action}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="max-w-md truncate">
                                                {log.description}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {log.ip_address}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {activityLogs.links && activityLogs.links.length > 3 && (
                    <div className="flex justify-center gap-2">
                        {activityLogs.links.map((link: PaginationLink, index: number) => (
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
