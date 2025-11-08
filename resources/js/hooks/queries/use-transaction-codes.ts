import { queryOptions, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

export interface TransactionCode {
    id: string;
    code_number: number;
    code_name: string;
    code_category: 'Earning' | 'Deduction' | 'Contribution';
    is_benefit: boolean;
    code_amount?: number;
    minimum_threshold?: number;
    maximum_threshold?: number;
    code_percentage?: number;
    is_editable: boolean;
    is_active: boolean;
    description?: string;
    formatted_code: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
}

interface TransactionCodesResponse {
    data: TransactionCode[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

interface TransactionCodeFilters {
    page?: number;
    per_page?: number;
    search?: string;
    category?: 'Earning' | 'Deduction' | 'Contribution';
    is_active?: boolean;
    is_benefit?: boolean;
}

// Query keys factory
export const transactionCodeKeys = {
    all: ['transaction-codes'] as const,
    lists: () => [...transactionCodeKeys.all, 'list'] as const,
    list: (filters: TransactionCodeFilters) => [...transactionCodeKeys.lists(), filters] as const,
    details: () => [...transactionCodeKeys.all, 'detail'] as const,
    detail: (id: string) => [...transactionCodeKeys.details(), id] as const,
};

// Fetch function
async function fetchTransactionCodes(filters: TransactionCodeFilters = {}): Promise<TransactionCodesResponse> {
    const params = new URLSearchParams();
    if (filters.page) params.append('page', filters.page.toString());
    if (filters.per_page) params.append('per_page', filters.per_page.toString());
    if (filters.search) params.append('search', filters.search);
    if (filters.category) params.append('category', filters.category);
    if (filters.is_active !== undefined) params.append('is_active', filters.is_active.toString());
    if (filters.is_benefit !== undefined) params.append('is_benefit', filters.is_benefit.toString());

    const response = await fetch(`/api/transaction-codes?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch transaction codes');
    }

    return response.json();
}

async function fetchTransactionCode(id: string): Promise<TransactionCode> {
    const response = await fetch(`/api/transaction-codes/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch transaction code');
    }

    return response.json();
}

// Query options factory
export function transactionCodesQueryOptions(filters: TransactionCodeFilters = {}) {
    return queryOptions({
        queryKey: transactionCodeKeys.list(filters),
        queryFn: () => fetchTransactionCodes(filters),
    });
}

export function transactionCodeQueryOptions(id: string) {
    return queryOptions({
        queryKey: transactionCodeKeys.detail(id),
        queryFn: () => fetchTransactionCode(id),
        enabled: !!id,
    });
}

// Hooks
export function useTransactionCodes(filters: TransactionCodeFilters = {}) {
    return useQuery(transactionCodesQueryOptions(filters));
}

export function useTransactionCode(id: string) {
    return useQuery(transactionCodeQueryOptions(id));
}

// Mutation for creating transaction code
export function useCreateTransactionCode() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<TransactionCode>) => {
            const response = await fetch('/api/transaction-codes', {
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
                throw new Error(error.message || 'Failed to create transaction code');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: transactionCodeKeys.lists() });
        },
    });
}

// Mutation for updating transaction code
export function useUpdateTransactionCode(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<TransactionCode>) => {
            const response = await fetch(`/api/transaction-codes/${id}`, {
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
                throw new Error(error.message || 'Failed to update transaction code');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: transactionCodeKeys.lists() });
            queryClient.invalidateQueries({ queryKey: transactionCodeKeys.detail(id) });
        },
    });
}

// Mutation for deleting transaction code
export function useDeleteTransactionCode() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            const response = await fetch(`/api/transaction-codes/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to delete transaction code');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: transactionCodeKeys.lists() });
        },
    });
}

// Constants for categories
export const TRANSACTION_CATEGORIES = {
    EARNING: 'Earning' as const,
    DEDUCTION: 'Deduction' as const,
    CONTRIBUTION: 'Contribution' as const,
};

export const CATEGORY_COLORS = {
    Earning: 'default' as const,
    Deduction: 'destructive' as const,
    Contribution: 'secondary' as const,
};
