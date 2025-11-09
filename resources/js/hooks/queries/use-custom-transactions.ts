import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';
import axios from 'axios';

// TypeScript Interfaces
export interface CustomTransaction {
    custom_id: number;
    center_id: string;
    period_id: number;
    worked_hours: number;
    base_hours: number;
    base_amount: number | null;
    use_basic: boolean;
    employee_count: number;
    transaction_count: number;
    amount_type: string;
    formatted_base_amount: string;
    work_ratio: number;
    employees?: Array<{
        id: string;
        firstname: string;
        surname: string;
        emp_system_id: string;
    }>;
    transaction_codes?: Array<{
        code_id: number;
        code_number: string;
        code_name: string;
    }>;
    period?: {
        period_id: number;
        month_name: string;
        period_year: number;
    };
    created_at: string;
    updated_at: string;
}

export interface CustomTransactionInput {
    period_id: number;
    worked_hours: number;
    base_hours: number;
    base_amount?: number;
    use_basic: boolean;
    employees: string[];
    transaction_codes: number[];
}

export interface CalculateEstimateInput {
    worked_hours: number;
    base_hours: number;
    base_amount?: number;
    use_basic: boolean;
    employee_id?: string;
}

// Query Keys
export const customTransactionKeys = {
    all: ['custom-transactions'] as const,
    lists: () => [...customTransactionKeys.all, 'list'] as const,
    list: (filters: any) => [...customTransactionKeys.lists(), filters] as const,
    details: () => [...customTransactionKeys.all, 'detail'] as const,
    detail: (id: number) => [...customTransactionKeys.details(), id] as const,
    employees: (id: number) => [...customTransactionKeys.all, 'employees', id] as const,
    codes: (id: number) => [...customTransactionKeys.all, 'codes', id] as const,
};

// Fetch Custom Transaction Details (AJAX)
export function useCustomTransactionDetails(transactionId: number, enabled: boolean = true) {
    return useQuery({
        queryKey: customTransactionKeys.detail(transactionId),
        queryFn: async () => {
            const response = await axios.get(`/custom-transactions/${transactionId}`);
            return response.data.data as CustomTransaction;
        },
        enabled,
    });
}

// Fetch Custom Transaction Employees (AJAX)
export function useCustomTransactionEmployees(transactionId: number, enabled: boolean = true) {
    return useQuery({
        queryKey: customTransactionKeys.employees(transactionId),
        queryFn: async () => {
            const response = await axios.get(`/custom-transactions/${transactionId}/employees`);
            return response.data.data;
        },
        enabled,
    });
}

// Fetch Custom Transaction Codes (AJAX)
export function useCustomTransactionCodes(transactionId: number, enabled: boolean = true) {
    return useQuery({
        queryKey: customTransactionKeys.codes(transactionId),
        queryFn: async () => {
            const response = await axios.get(`/custom-transactions/${transactionId}/codes`);
            return response.data.data;
        },
        enabled,
    });
}

// Calculate Estimate (AJAX)
export function useCalculateEstimate() {
    return useMutation({
        mutationFn: async (data: CalculateEstimateInput) => {
            const response = await axios.post('/custom-transactions/calculate-estimate', data);
            return response.data.data;
        },
    });
}

// Create Custom Transaction Mutation
export function useCreateCustomTransaction() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: CustomTransactionInput) => {
            return new Promise((resolve, reject) => {
                router.post('/custom-transactions', data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: customTransactionKeys.all });
        },
    });
}

// Update Custom Transaction Mutation
export function useUpdateCustomTransaction(transactionId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: Omit<CustomTransactionInput, 'period_id'>) => {
            return new Promise((resolve, reject) => {
                router.put(`/custom-transactions/${transactionId}`, data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: customTransactionKeys.all });
            queryClient.invalidateQueries({ queryKey: customTransactionKeys.detail(transactionId) });
        },
    });
}

// Delete Custom Transaction Mutation
export function useDeleteCustomTransaction() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (transactionId: number) => {
            return new Promise((resolve, reject) => {
                router.delete(`/custom-transactions/${transactionId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: customTransactionKeys.all });
        },
    });
}
