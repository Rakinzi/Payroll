import { router } from '@inertiajs/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export interface CurrencySplit {
    id: string;
    center_id: string;
    zwg_percentage: number;
    usd_percentage: number;
    effective_date: string;
    is_active: boolean;
    notes: string | null;
    formatted_zwg_percentage: string;
    formatted_usd_percentage: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    cost_center?: {
        id: string;
        name: string;
    };
}

export interface CurrencySplitData {
    center_id: string;
    zwg_percentage: number;
    usd_percentage: number;
    effective_date: string;
    is_active: boolean;
    notes?: string | null;
}

export interface ExchangeRate {
    id: string;
    from_currency: string;
    to_currency: string;
    rate: number;
    effective_date: string;
    is_active: boolean;
    formatted_rate: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface ExchangeRateData {
    from_currency: string;
    to_currency: string;
    rate: number;
    effective_date: string;
    is_active: boolean;
}

export const CURRENCIES = ['USD', 'ZWG', 'ZWG', 'RTGS'] as const;
export type Currency = typeof CURRENCIES[number];

// Query keys factory
export const currencySetupKeys = {
    all: ['currency-setup'] as const,
    splits: () => [...currencySetupKeys.all, 'splits'] as const,
    rates: () => [...currencySetupKeys.all, 'rates'] as const,
};

// ============================================
// Currency Split Mutations
// ============================================

export function useCreateCurrencySplit() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: CurrencySplitData) => {
            return new Promise((resolve, reject) => {
                router.post('/currency-setup/splits', data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: currencySetupKeys.splits() });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

export function useUpdateCurrencySplit(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: CurrencySplitData) => {
            return new Promise((resolve, reject) => {
                router.put(`/currency-setup/splits/${id}`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: currencySetupKeys.splits() });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

export function useDeleteCurrencySplit() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/currency-setup/splits/${id}`, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: currencySetupKeys.splits() });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

// ============================================
// Exchange Rate Mutations
// ============================================

export function useCreateExchangeRate() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: ExchangeRateData) => {
            return new Promise((resolve, reject) => {
                router.post('/currency-setup/rates', data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: currencySetupKeys.rates() });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

export function useUpdateExchangeRate(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: ExchangeRateData) => {
            return new Promise((resolve, reject) => {
                router.put(`/currency-setup/rates/${id}`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: currencySetupKeys.rates() });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

export function useDeleteExchangeRate() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/currency-setup/rates/${id}`, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: currencySetupKeys.rates() });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}
