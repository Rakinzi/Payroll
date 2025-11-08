import { queryOptions, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';

interface Employee {
    id: string;
    emp_system_id: string;
    firstname: string;
    surname: string;
    othername?: string;
    center_id: string;
    is_ex: boolean;
    date_engaged?: string;
    is_ex_on?: string;
    created_at: string;
    updated_at: string;
}

interface EmployeesResponse {
    data: Employee[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

interface EmployeeFilters {
    page?: number;
    per_page?: number;
    search?: string;
    center_id?: string;
    is_ex?: boolean;
}

// Query keys factory
export const employeeKeys = {
    all: ['employees'] as const,
    lists: () => [...employeeKeys.all, 'list'] as const,
    list: (filters: EmployeeFilters) => [...employeeKeys.lists(), filters] as const,
    details: () => [...employeeKeys.all, 'detail'] as const,
    detail: (id: string) => [...employeeKeys.details(), id] as const,
};

// Fetch function
async function fetchEmployees(filters: EmployeeFilters = {}): Promise<EmployeesResponse> {
    const params = new URLSearchParams();
    if (filters.page) params.append('page', filters.page.toString());
    if (filters.per_page) params.append('per_page', filters.per_page.toString());
    if (filters.search) params.append('search', filters.search);
    if (filters.center_id) params.append('center_id', filters.center_id);
    if (filters.is_ex !== undefined) params.append('is_ex', filters.is_ex.toString());

    const response = await fetch(`/api/employees?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch employees');
    }

    return response.json();
}

async function fetchEmployee(id: string): Promise<Employee> {
    const response = await fetch(`/api/employees/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch employee');
    }

    return response.json();
}

// Query options factory
export function employeesQueryOptions(filters: EmployeeFilters = {}) {
    return queryOptions({
        queryKey: employeeKeys.list(filters),
        queryFn: () => fetchEmployees(filters),
    });
}

export function employeeQueryOptions(id: string) {
    return queryOptions({
        queryKey: employeeKeys.detail(id),
        queryFn: () => fetchEmployee(id),
        enabled: !!id,
    });
}

// Hooks
export function useEmployees(filters: EmployeeFilters = {}) {
    return useQuery(employeesQueryOptions(filters));
}

export function useEmployee(id: string) {
    return useQuery(employeeQueryOptions(id));
}

// Mutation for creating employee
export function useCreateEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<Employee>) => {
            const response = await fetch('/api/employees', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to create employee');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
        },
    });
}

// Mutation for updating employee
export function useUpdateEmployee(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<Employee>) => {
            const response = await fetch(`/api/employees/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to update employee');
            }

            return response.json();
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
            queryClient.invalidateQueries({ queryKey: employeeKeys.detail(id) });
        },
    });
}

// Mutation for deleting employee
export function useDeleteEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            const response = await fetch(`/api/employees/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to delete employee');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
        },
    });
}
