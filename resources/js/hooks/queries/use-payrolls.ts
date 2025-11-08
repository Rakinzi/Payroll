import { useMutation, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';

// TypeScript Interfaces
export interface Payroll {
    id: string;
    payroll_name: string;
    payroll_type: 'Period' | 'Daily' | 'Hourly';
    payroll_period: 12 | 26 | 52;
    start_date: string;
    tax_method: string;
    payroll_currency: string;
    description: string | null;
    is_active: boolean;
    period_type: string;
    currency_display: string;
    active_employee_count: number;
    created_at: string;
    updated_at: string;
}

export interface PayrollFormData {
    payroll_name: string;
    payroll_type: 'Period' | 'Daily' | 'Hourly';
    payroll_period: 12 | 26 | 52;
    start_date: string;
    tax_method: string;
    payroll_currency: string;
    description?: string;
    is_active: boolean;
}

export interface AssignEmployeesData {
    employee_ids: string[];
}

// Query Keys
export const payrollKeys = {
    all: ['payrolls'] as const,
    lists: () => [...payrollKeys.all, 'list'] as const,
    list: (filters: string) => [...payrollKeys.lists(), { filters }] as const,
    details: () => [...payrollKeys.all, 'detail'] as const,
    detail: (id: string) => [...payrollKeys.details(), id] as const,
};

// Create Payroll Mutation
export function useCreatePayroll() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: PayrollFormData) => {
            return new Promise((resolve, reject) => {
                router.post('/payrolls', data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payrollKeys.all });
        },
    });
}

// Update Payroll Mutation
export function useUpdatePayroll(payrollId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: PayrollFormData) => {
            return new Promise((resolve, reject) => {
                router.put(`/payrolls/${payrollId}`, data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payrollKeys.all });
            queryClient.invalidateQueries({ queryKey: payrollKeys.detail(payrollId) });
        },
    });
}

// Delete Payroll Mutation
export function useDeletePayroll() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payrollId: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/payrolls/${payrollId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payrollKeys.all });
        },
    });
}

// Toggle Payroll Status Mutation
export function useTogglePayrollStatus() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payrollId: string) => {
            return new Promise((resolve, reject) => {
                router.post(`/payrolls/${payrollId}/toggle-status`, {}, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payrollKeys.all });
        },
    });
}

// Assign Employees Mutation
export function useAssignEmployees(payrollId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: AssignEmployeesData) => {
            return new Promise((resolve, reject) => {
                router.post(`/payrolls/${payrollId}/assign-employees`, data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payrollKeys.all });
            queryClient.invalidateQueries({ queryKey: payrollKeys.detail(payrollId) });
        },
    });
}

// Remove Employee Mutation
export function useRemoveEmployee(payrollId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (employeeId: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/payrolls/${payrollId}/employees/${employeeId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payrollKeys.all });
            queryClient.invalidateQueries({ queryKey: payrollKeys.detail(payrollId) });
        },
    });
}
