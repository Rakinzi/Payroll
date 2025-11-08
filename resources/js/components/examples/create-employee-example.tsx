/**
 * Example Component: Create Employee Form
 *
 * This component demonstrates:
 * - Using React Query mutations (useCreateEmployee)
 * - Using Zustand for form state instead of useState
 * - Using custom hooks for notifications
 * - Automatic cache invalidation after mutation
 */

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCostCenters } from '@/hooks/queries/use-cost-centers';
import { useCreateEmployee } from '@/hooks/queries/use-employees';
import { useNotification } from '@/hooks/use-notification';
import { FormEvent } from 'react';
import { create } from 'zustand';

// Form-specific Zustand store (scoped to this component)
interface EmployeeFormState {
    firstname: string;
    surname: string;
    othername: string;
    emp_system_id: string;
    center_id: string;
    setField: (field: string, value: string) => void;
    reset: () => void;
}

const useEmployeeFormStore = create<EmployeeFormState>((set) => ({
    firstname: '',
    surname: '',
    othername: '',
    emp_system_id: '',
    center_id: '',
    setField: (field, value) => set({ [field]: value }),
    reset: () =>
        set({
            firstname: '',
            surname: '',
            othername: '',
            emp_system_id: '',
            center_id: '',
        }),
}));

export function CreateEmployeeExample() {
    // Zustand form state - replaces multiple useState calls
    const formState = useEmployeeFormStore();
    const notification = useNotification();

    // React Query for fetching cost centers
    const { data: costCenters, isLoading: loadingCenters } = useCostCenters({ active: true });

    // React Query mutation for creating employee
    const createMutation = useCreateEmployee();

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();

        try {
            await createMutation.mutateAsync({
                firstname: formState.firstname,
                surname: formState.surname,
                othername: formState.othername || undefined,
                emp_system_id: formState.emp_system_id,
                center_id: formState.center_id,
            });

            notification.success('Employee created successfully!', 'Success');
            formState.reset();
        } catch (error) {
            notification.error(
                error instanceof Error ? error.message : 'Failed to create employee',
                'Error',
            );
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Create New Employee</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="emp_system_id">Employee ID</Label>
                            <Input
                                id="emp_system_id"
                                value={formState.emp_system_id}
                                onChange={(e) => formState.setField('emp_system_id', e.target.value)}
                                placeholder="EMP001"
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="center_id">Cost Center</Label>
                            <Select
                                value={formState.center_id}
                                onValueChange={(value) => formState.setField('center_id', value)}
                                required
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select cost center" />
                                </SelectTrigger>
                                <SelectContent>
                                    {loadingCenters ? (
                                        <SelectItem value="" disabled>
                                            Loading...
                                        </SelectItem>
                                    ) : (
                                        costCenters?.map((center) => (
                                            <SelectItem key={center.id} value={center.id}>
                                                {center.center_name} ({center.center_code})
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="firstname">First Name</Label>
                            <Input
                                id="firstname"
                                value={formState.firstname}
                                onChange={(e) => formState.setField('firstname', e.target.value)}
                                placeholder="John"
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="surname">Surname</Label>
                            <Input
                                id="surname"
                                value={formState.surname}
                                onChange={(e) => formState.setField('surname', e.target.value)}
                                placeholder="Doe"
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="othername">Other Name</Label>
                            <Input
                                id="othername"
                                value={formState.othername}
                                onChange={(e) => formState.setField('othername', e.target.value)}
                                placeholder="Optional"
                            />
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={createMutation.isPending}>
                            {createMutation.isPending ? 'Creating...' : 'Create Employee'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={formState.reset}
                            disabled={createMutation.isPending}
                        >
                            Reset
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
