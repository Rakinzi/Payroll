import { queryOptions, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

export interface TaxCredit {
    id: string;
    credit_name: string;
    credit_amount: number;
    currency: 'USD' | 'ZWG';
    period: 'monthly' | 'annual';
    description?: string;
    is_active: boolean;
    formatted_value: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
}

interface TaxCreditsResponse {
    data: TaxCredit[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

interface TaxCreditFilters {
    page?: number;
    per_page?: number;
    search?: string;
    currency?: 'USD' | 'ZWG';
    period?: 'monthly' | 'annual';
    is_active?: boolean;
}

// Query keys factory
export const taxCreditKeys = {
    all: ['tax-credits'] as const,
    lists: () => [...taxCreditKeys.all, 'list'] as const,
    list: (filters: TaxCreditFilters) => [...taxCreditKeys.lists(), filters] as const,
    details: () => [...taxCreditKeys.all, 'detail'] as const,
    detail: (id: string) => [...taxCreditKeys.details(), id] as const,
};

// Fetch function
async function fetchTaxCredits(filters: TaxCreditFilters = {}): Promise<TaxCreditsResponse> {
    const params = new URLSearchParams();
    if (filters.page) params.append('page', filters.page.toString());
    if (filters.per_page) params.append('per_page', filters.per_page.toString());
    if (filters.search) params.append('search', filters.search);
    if (filters.currency) params.append('currency', filters.currency);
    if (filters.period) params.append('period', filters.period);
    if (filters.is_active !== undefined) params.append('is_active', filters.is_active.toString());

    const response = await fetch(`/api/tax-credits?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch tax credits');
    }

    return response.json();
}

async function fetchTaxCredit(id: string): Promise<TaxCredit> {
    const response = await fetch(`/api/tax-credits/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch tax credit');
    }

    return response.json();
}

// Query options factory
export function taxCreditsQueryOptions(filters: TaxCreditFilters = {}) {
    return queryOptions({
        queryKey: taxCreditKeys.list(filters),
        queryFn: () => fetchTaxCredits(filters),
    });
}

export function taxCreditQueryOptions(id: string) {
    return queryOptions({
        queryKey: taxCreditKeys.detail(id),
        queryFn: () => fetchTaxCredit(id),
        enabled: !!id,
    });
}

// Hooks
export function useTaxCredits(filters: TaxCreditFilters = {}) {
    return useQuery(taxCreditsQueryOptions(filters));
}

export function useTaxCredit(id: string) {
    return useQuery(taxCreditQueryOptions(id));
}

// Mutation for creating tax credit
export function useCreateTaxCredit() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<TaxCredit>) => {
            const response = await fetch('/api/tax-credits', {
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
                throw new Error(error.message || 'Failed to create tax credit');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: taxCreditKeys.lists() });
        },
    });
}

// Mutation for updating tax credit
export function useUpdateTaxCredit(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<TaxCredit>) => {
            const response = await fetch(`/api/tax-credits/${id}`, {
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
                throw new Error(error.message || 'Failed to update tax credit');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: taxCreditKeys.lists() });
            queryClient.invalidateQueries({ queryKey: taxCreditKeys.detail(id) });
        },
    });
}

// Mutation for deleting tax credit
export function useDeleteTaxCredit() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            const response = await fetch(`/api/tax-credits/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to delete tax credit');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: taxCreditKeys.lists() });
        },
    });
}

// Constants for currencies and periods
export const TAX_CREDIT_CURRENCIES = {
    USD: 'USD' as const,
    ZWG: 'ZWG' as const,
};

export const TAX_CREDIT_PERIODS = {
    MONTHLY: 'monthly' as const,
    ANNUAL: 'annual' as const,
};

export const CURRENCY_COLORS = {
    USD: 'default' as const,
    ZWG: 'secondary' as const,
};

export const PERIOD_COLORS = {
    monthly: 'default' as const,
    annual: 'secondary' as const,
};
