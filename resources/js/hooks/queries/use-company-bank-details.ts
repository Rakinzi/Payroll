import { router } from '@inertiajs/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export interface CompanyBankDetail {
    id: string;
    center_id: string;
    bank_name: string;
    branch_name: string;
    branch_code: string;
    account_number: string; // Decrypted
    masked_account_number: string;
    account_type: 'Current' | 'Nostro' | 'FCA';
    account_currency: 'RTGS' | 'ZWG' | 'USD';
    is_default: boolean;
    is_active: boolean;
    cost_center?: {
        id: string;
        center_name: string;
        center_code: string;
    };
    created_at: string;
    updated_at: string;
}

export interface CompanyBankDetailData {
    bank_name: string;
    branch_name: string;
    branch_code: string;
    account_number: string;
    account_type: 'Current' | 'Nostro' | 'FCA';
    account_currency: 'RTGS' | 'ZWG' | 'USD';
    is_default: boolean;
}

export const ACCOUNT_TYPES = ['Current', 'Nostro', 'FCA'] as const;
export const CURRENCIES = ['RTGS', 'ZWG', 'USD'] as const;

export const ACCOUNT_TYPE_COLORS = {
    Current: 'default',
    Nostro: 'secondary',
    FCA: 'outline',
} as const;

export const CURRENCY_COLORS = {
    USD: 'default',
    ZWG: 'secondary',
    RTGS: 'outline',
} as const;

// Query keys factory
export const companyBankDetailKeys = {
    all: ['company-bank-details'] as const,
    lists: () => [...companyBankDetailKeys.all, 'list'] as const,
    details: () => [...companyBankDetailKeys.all, 'detail'] as const,
    detail: (id: string) => [...companyBankDetailKeys.details(), id] as const,
};

// Mutation for creating company bank detail
export function useCreateCompanyBankDetail() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: CompanyBankDetailData) => {
            return new Promise((resolve, reject) => {
                router.post('/company-bank-details', data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: companyBankDetailKeys.lists() });
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

// Mutation for updating company bank detail
export function useUpdateCompanyBankDetail(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: CompanyBankDetailData) => {
            return new Promise((resolve, reject) => {
                router.put(`/company-bank-details/${id}`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: companyBankDetailKeys.lists() });
                        queryClient.invalidateQueries({ queryKey: companyBankDetailKeys.detail(id) });
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

// Mutation for deleting company bank detail
export function useDeleteCompanyBankDetail() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/company-bank-details/${id}`, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: companyBankDetailKeys.lists() });
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

// Mutation for setting default bank account
export function useSetDefaultBankAccount() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            return new Promise((resolve, reject) => {
                router.post(`/company-bank-details/${id}/set-default`, {}, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: companyBankDetailKeys.lists() });
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
