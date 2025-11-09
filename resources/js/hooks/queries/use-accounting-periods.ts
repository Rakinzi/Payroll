import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';
import axios from 'axios';

// TypeScript Interfaces
export interface CenterPeriodStatus {
    status_id: number;
    period_id: number;
    center_id: string;
    center_name?: string;
    period_currency: 'ZWG' | 'USD' | 'DEFAULT';
    period_run_date: string | null;
    pay_run_date: string | null;
    is_closed_confirmed: boolean | null;
    is_completed: boolean;
    can_be_run: boolean;
    can_be_refreshed: boolean;
    can_be_closed: boolean;
    status_display: string;
    created_at: string;
    updated_at: string;
}

export interface AccountingPeriod {
    period_id: number;
    payroll_id: string;
    month_name: string;
    period_year: number;
    period_start: string;
    period_end: string;
    status: 'Current' | 'Future' | 'Past';
    is_current: boolean;
    is_future: boolean;
    is_past: boolean;
    completion_percentage: number;
    period_display: string;
    center_statuses: CenterPeriodStatus[];
    created_at: string;
    updated_at: string;
}

export interface PeriodRunData {
    currency: 'ZWG' | 'USD' | 'DEFAULT';
}

export interface PeriodSummary {
    total_employees: number;
    total_gross_zwg: number;
    total_gross_usd: number;
    total_deductions_zwg: number;
    total_deductions_usd: number;
    total_net_zwg: number;
    total_net_usd: number;
    centers_completed: number;
    centers_total: number;
}

export interface GeneratePeriodsData {
    payroll_id: string;
    year: number;
}

// Query Keys
export const accountingPeriodKeys = {
    all: ['accounting-periods'] as const,
    lists: () => [...accountingPeriodKeys.all, 'list'] as const,
    list: (filters: any) => [...accountingPeriodKeys.lists(), filters] as const,
    details: () => [...accountingPeriodKeys.all, 'detail'] as const,
    detail: (id: number) => [...accountingPeriodKeys.details(), id] as const,
    status: (id: number) => [...accountingPeriodKeys.all, 'status', id] as const,
    summary: (id: number) => [...accountingPeriodKeys.all, 'summary', id] as const,
};

// Fetch Period Status (AJAX)
export function usePeriodStatus(periodId: number, enabled: boolean = true) {
    return useQuery({
        queryKey: accountingPeriodKeys.status(periodId),
        queryFn: async () => {
            const response = await axios.get(`/accounting-periods/${periodId}/status`);
            return response.data as {
                status: string;
                is_current: boolean;
                is_future: boolean;
                is_past: boolean;
                completion_percentage: number;
                is_fully_completed: boolean;
                center_statuses: CenterPeriodStatus[];
            };
        },
        enabled,
        refetchInterval: 10000, // Refetch every 10 seconds for real-time updates
    });
}

// Fetch Period Summary (AJAX)
export function usePeriodSummary(periodId: number, enabled: boolean = true) {
    return useQuery({
        queryKey: accountingPeriodKeys.summary(periodId),
        queryFn: async () => {
            const response = await axios.get(`/accounting-periods/${periodId}/summary`);
            return response.data as PeriodSummary;
        },
        enabled,
    });
}

// Run Period Mutation
export function useRunPeriod(periodId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: PeriodRunData) => {
            return new Promise((resolve, reject) => {
                router.post(`/accounting-periods/${periodId}/run`, data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.all });
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.status(periodId) });
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.summary(periodId) });
        },
    });
}

// Refresh Period Mutation
export function useRefreshPeriod(periodId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: PeriodRunData) => {
            return new Promise((resolve, reject) => {
                router.post(`/accounting-periods/${periodId}/refresh`, data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.all });
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.status(periodId) });
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.summary(periodId) });
        },
    });
}

// Close Period Mutation
export function useClosePeriod(periodId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => {
            return new Promise((resolve, reject) => {
                router.post(`/accounting-periods/${periodId}/close`, {}, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.all });
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.status(periodId) });
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.summary(periodId) });
        },
    });
}

// Update Period Currency Mutation
export function useUpdatePeriodCurrency(periodId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: PeriodRunData) => {
            const response = await axios.post(
                `/accounting-periods/${periodId}/currency`,
                data
            );
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.all });
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.status(periodId) });
        },
    });
}

// Generate Periods Mutation
export function useGeneratePeriods() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: GeneratePeriodsData) => {
            return new Promise((resolve, reject) => {
                router.post('/accounting-periods/generate', data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: accountingPeriodKeys.all });
        },
    });
}

// Export Period Data
export function useExportPeriod(periodId: number) {
    return useMutation({
        mutationFn: async () => {
            const response = await axios.get(
                `/accounting-periods/${periodId}/export`,
                { responseType: 'blob' }
            );

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `period-${periodId}-export.xlsx`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            return true;
        },
    });
}
