/**
 * Example Component: Employee List
 *
 * This component demonstrates:
 * - Using React Query for data fetching (useEmployees)
 * - Using Zustand for UI state (useUIStore for notifications)
 * - Using custom hooks (useNotification)
 * - No useState or useEffect needed
 */

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useDeleteEmployee, useEmployees } from '@/hooks/queries/use-employees';
import { useNotification } from '@/hooks/use-notification';
import { useAuthStore } from '@/stores/auth-store';
import { useUIStore } from '@/stores/ui-store';
import { Trash2, UserPlus } from 'lucide-react';
import { ChangeEvent } from 'react';

export function EmployeeListExample() {
    // Zustand stores - no useState needed
    const hasPermission = useAuthStore((state) => state.hasPermission);
    const globalLoading = useUIStore((state) => state.globalLoading);
    const setGlobalLoading = useUIStore((state) => state.setGlobalLoading);

    // Custom notification hook
    const notification = useNotification();

    // React Query for data fetching - no useEffect needed
    const { data, isLoading, error, refetch } = useEmployees({
        page: 1,
        per_page: 10,
    });

    // React Query mutation for deleting
    const deleteMutation = useDeleteEmployee();

    const handleDelete = async (id: string, name: string) => {
        if (!confirm(`Are you sure you want to delete ${name}?`)) return;

        setGlobalLoading(true);
        try {
            await deleteMutation.mutateAsync(id);
            notification.success(`Employee ${name} deleted successfully`);
        } catch (error) {
            notification.error(
                error instanceof Error ? error.message : 'Failed to delete employee',
            );
        } finally {
            setGlobalLoading(false);
        }
    };

    const handleSearch = (e: ChangeEvent<HTMLInputElement>) => {
        // You can use debounced search with React Query
        // For now, this is just a placeholder
        console.log('Search:', e.target.value);
    };

    if (isLoading) {
        return (
            <div className="flex h-64 items-center justify-center">
                <Spinner className="h-8 w-8" />
            </div>
        );
    }

    if (error) {
        return (
            <Card className="border-destructive">
                <CardContent className="pt-6">
                    <p className="text-destructive">
                        Error: {error instanceof Error ? error.message : 'Failed to load employees'}
                    </p>
                    <Button onClick={() => refetch()} className="mt-4">
                        Retry
                    </Button>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle>Employees</CardTitle>
                    {hasPermission('create employees') && (
                        <Button>
                            <UserPlus className="mr-2 h-4 w-4" />
                            Add Employee
                        </Button>
                    )}
                </div>
                <div className="mt-4">
                    <Input
                        placeholder="Search employees..."
                        onChange={handleSearch}
                        className="max-w-sm"
                    />
                </div>
            </CardHeader>
            <CardContent>
                {data?.data && data.data.length > 0 ? (
                    <div className="space-y-2">
                        {data.data.map((employee) => (
                            <div
                                key={employee.id}
                                className="flex items-center justify-between rounded-lg border p-4"
                            >
                                <div>
                                    <p className="font-medium">
                                        {employee.firstname} {employee.surname}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        ID: {employee.emp_system_id}
                                    </p>
                                </div>
                                {hasPermission('delete employees') && (
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() =>
                                            handleDelete(
                                                employee.id,
                                                `${employee.firstname} ${employee.surname}`,
                                            )
                                        }
                                        disabled={globalLoading || deleteMutation.isPending}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="py-8 text-center text-muted-foreground">No employees found</p>
                )}

                {data?.meta && (
                    <div className="mt-4 text-sm text-muted-foreground">
                        Showing {data.data.length} of {data.meta.total} employees
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
