import { router } from '@inertiajs/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export interface TaxBand {
    id: number;
    min_salary: number;
    max_salary: number | null;
    tax_rate: number; // Stored as decimal (0.20 for 20%)
    tax_amount: number;
    formatted_min_salary: string;
    formatted_max_salary: string;
    formatted_rate: string;
    created_at: string;
    updated_at: string;
}

export interface TaxBandData {
    min_salary: number;
    max_salary: number | null;
    tax_rate: number; // Should be decimal (0.20 for 20%)
    tax_amount: number;
}

export type BandType = 'annual_zwg' | 'annual_usd' | 'monthly_zwg' | 'monthly_usd';

export const BAND_TYPE_LABELS: Record<BandType, string> = {
    annual_zwg: 'ZWG - Annual Table',
    annual_usd: 'USD - Annual Table',
    monthly_zwg: 'ZWG - Monthly Table',
    monthly_usd: 'USD - Monthly Table',
};

export const BAND_TYPES: BandType[] = ['annual_zwg', 'annual_usd', 'monthly_zwg', 'monthly_usd'];

// Query keys factory
export const taxBandKeys = {
    all: ['tax-bands'] as const,
    lists: () => [...taxBandKeys.all, 'list'] as const,
    list: (bandType: BandType) => [...taxBandKeys.lists(), bandType] as const,
};

// Mutation for updating tax band
export function useUpdateTaxBand(bandType: BandType, id: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: TaxBandData) => {
            return new Promise((resolve, reject) => {
                router.put(`/tax-bands/${bandType}/${id}`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: taxBandKeys.lists() });
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

// Mutation for creating tax band
export function useCreateTaxBand(bandType: BandType) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: TaxBandData) => {
            return new Promise((resolve, reject) => {
                router.post(`/tax-bands/${bandType}`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: taxBandKeys.lists() });
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

// Mutation for deleting tax band
export function useDeleteTaxBand(bandType: BandType) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: number) => {
            return new Promise((resolve, reject) => {
                router.delete(`/tax-bands/${bandType}/${id}`, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: taxBandKeys.lists() });
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
