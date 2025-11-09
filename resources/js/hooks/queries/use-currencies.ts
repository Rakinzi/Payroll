import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';
import axios from 'axios';

// Types
export interface Currency {
    currency_id: number;
    code: string;
    name: string;
    symbol: string;
    exchange_rate: number;
    formatted_rate: string;
    is_base: boolean;
    is_active: boolean;
    decimal_places: number;
    description?: string;
    display_name: string;
    created_at: string;
    updated_at: string;
}

export interface CurrencyFormData {
    code: string;
    name: string;
    symbol: string;
    exchange_rate: number;
    decimal_places: number;
    description?: string;
}

export interface CurrencyUpdateData {
    name: string;
    symbol: string;
    exchange_rate: number;
    decimal_places: number;
    description?: string;
}

// Query Keys
export const currencyKeys = {
    all: ['currencies'] as const,
    lists: () => [...currencyKeys.all, 'list'] as const,
    list: (filters: string) => [...currencyKeys.lists(), { filters }] as const,
    details: () => [...currencyKeys.all, 'detail'] as const,
    detail: (id: number) => [...currencyKeys.details(), id] as const,
    active: () => [...currencyKeys.all, 'active'] as const,
};

// Queries
export function useActiveCurrencies() {
    return useQuery({
        queryKey: currencyKeys.active(),
        queryFn: async () => {
            const response = await axios.get('/settings/currencies/active/all');
            return response.data.currencies as Currency[];
        },
    });
}

export function useCurrencyDetails(currencyId: number, enabled: boolean = true) {
    return useQuery({
        queryKey: currencyKeys.detail(currencyId),
        queryFn: async () => {
            const response = await axios.get(`/settings/currencies/${currencyId}`);
            return response.data.currency as Currency;
        },
        enabled,
    });
}

// Mutations
export function useCreateCurrency() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: CurrencyFormData) => {
            return new Promise((resolve, reject) => {
                router.post('/settings/currencies', data, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: currencyKeys.all });
        },
    });
}

export function useUpdateCurrency(currencyId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: CurrencyUpdateData) => {
            return new Promise((resolve, reject) => {
                router.put(`/settings/currencies/${currencyId}`, data, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: currencyKeys.all });
            queryClient.invalidateQueries({ queryKey: currencyKeys.detail(currencyId) });
        },
    });
}

export function useDeleteCurrency() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (currencyId: number) => {
            return new Promise((resolve, reject) => {
                router.delete(`/settings/currencies/${currencyId}`, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: currencyKeys.all });
        },
    });
}

export function useToggleCurrencyStatus() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (currencyId: number) => {
            return new Promise((resolve, reject) => {
                router.post(`/settings/currencies/${currencyId}/toggle-status`, {}, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: currencyKeys.all });
        },
    });
}

export function useSetBaseCurrency() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (currencyId: number) => {
            return new Promise((resolve, reject) => {
                router.post(`/settings/currencies/${currencyId}/set-base`, {}, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: currencyKeys.all });
        },
    });
}

export async function getExchangeRate(from: string, to: string): Promise<number> {
    const response = await axios.post('/settings/currencies/exchange-rate', { from, to });
    return response.data.rate;
}

export async function convertCurrency(amount: number, from: string, to: string): Promise<number> {
    const response = await axios.post('/settings/currencies/convert', { amount, from, to });
    return response.data.converted_amount;
}
