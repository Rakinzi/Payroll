import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';
import axios from 'axios';

// TypeScript Interfaces
export interface DefaultTransaction {
    default_id: number;
    code_id: number;
    period_id: number;
    center_id: string;
    transaction_effect: '+' | '-';
    employee_amount: number;
    employer_amount: number;
    hours_worked: number;
    transaction_currency: 'ZWL' | 'USD';
    total_amount: number;
    effect_display: string;
    currency_symbol: string;
    transaction_code?: {
        code_id: number;
        code_number: string;
        code_name: string;
    };
    created_at: string;
    updated_at: string;
}

export interface DefaultTransactionInput {
    code_id: number;
    transaction_effect: '+' | '-';
    employee_amount: number;
    employer_amount?: number;
    hours_worked?: number;
    transaction_currency: 'ZWL' | 'USD';
}

export interface SaveDefaultTransactionsData {
    period_id: number;
    transactions: DefaultTransactionInput[];
}

// Query Keys
export const defaultTransactionKeys = {
    all: ['default-transactions'] as const,
    lists: () => [...defaultTransactionKeys.all, 'list'] as const,
    currentPeriod: () => [...defaultTransactionKeys.lists(), 'current'] as const,
};

// Fetch Transaction Codes (AJAX)
export function useTransactionCodes() {
    return useQuery({
        queryKey: ['transaction-codes'],
        queryFn: async () => {
            const response = await axios.get('/default-transactions/transaction-codes');
            return response.data.data;
        },
        staleTime: 1000 * 60 * 10, // 10 minutes
    });
}

// Save Default Transactions Mutation
export function useSaveDefaultTransactions() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: SaveDefaultTransactionsData) => {
            return new Promise((resolve, reject) => {
                router.post('/default-transactions', data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: defaultTransactionKeys.all });
        },
    });
}

// Delete Default Transaction Mutation
export function useDeleteDefaultTransaction() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (transactionId: number) => {
            return new Promise((resolve, reject) => {
                router.delete(`/default-transactions/${transactionId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: defaultTransactionKeys.all });
        },
    });
}

// Clear All Default Transactions Mutation
export function useClearAllDefaultTransactions() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (periodId: number) => {
            return new Promise((resolve, reject) => {
                router.post('/default-transactions/clear-all', { period_id: periodId }, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: defaultTransactionKeys.all });
        },
    });
}
