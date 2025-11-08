import { useMutation, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';

// TypeScript Interfaces
export interface Employee {
    id: string;
    emp_system_id: string;
    full_name: string;
    email?: string;
    position?: {
        position_name: string;
    };
    department?: {
        department_name: string;
    };
}

export interface Payroll {
    id: string;
    payroll_name: string;
}

export interface TransactionCode {
    code: string;
    description: string;
}

export interface PayslipTransaction {
    id: string;
    description: string;
    transaction_type: 'earning' | 'deduction';
    type_display: string;
    amount_zwg: number;
    amount_usd: number;
    is_taxable: boolean;
    is_recurring: boolean;
    is_manual: boolean;
    transaction_code?: TransactionCode;
}

export interface DistributionLog {
    id: string;
    recipient_email: string;
    recipient_name: string;
    status: 'pending' | 'sent' | 'failed' | 'bounced';
    status_display: string;
    sent_at?: string;
    error_message?: string;
    sender: {
        name: string;
    };
}

export interface Payslip {
    id: string;
    payslip_number: string;
    employee: Employee;
    payroll: Payroll;
    period_display: string;
    period_month: number;
    period_year: number;
    payment_date: string;
    status: 'draft' | 'finalized' | 'distributed' | 'cancelled';
    status_display: string;
    gross_salary_zwg: string | number;
    total_deductions_zwg: string | number;
    net_salary_zwg: string | number;
    gross_salary_usd: string | number;
    total_deductions_usd: string | number;
    net_salary_usd: string | number;
    ytd_gross_zwg?: number;
    ytd_gross_usd?: number;
    ytd_paye_zwg?: number;
    ytd_paye_usd?: number;
    exchange_rate?: number;
    notes?: string;
    can_be_edited: boolean;
    can_be_finalized: boolean;
    can_be_distributed: boolean;
    transactions?: PayslipTransaction[];
    distribution_logs?: DistributionLog[];
    created_at: string;
    updated_at?: string;
}

export interface PayslipFormData {
    employee_id: string;
    payroll_id: string;
    period_month: number;
    period_year: number;
    payment_date: string;
    exchange_rate?: number;
    notes?: string;
}

export interface TransactionFormData {
    transaction_code_id?: string;
    description: string;
    transaction_type: 'earning' | 'deduction';
    amount_zwg?: number;
    amount_usd?: number;
    is_taxable?: boolean;
    is_recurring?: boolean;
    is_manual?: boolean;
    notes?: string;
}

export interface DistributeFormData {
    recipient_email: string;
    recipient_name: string;
}

// Query Keys
export const payslipKeys = {
    all: ['payslips'] as const,
    lists: () => [...payslipKeys.all, 'list'] as const,
    list: (filters: string) => [...payslipKeys.lists(), { filters }] as const,
    details: () => [...payslipKeys.all, 'detail'] as const,
    detail: (id: string) => [...payslipKeys.details(), id] as const,
};

// Create Payslip Mutation
export function useCreatePayslip() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: PayslipFormData) => {
            return new Promise((resolve, reject) => {
                router.post('/payslips', data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payslipKeys.all });
        },
    });
}

// Add Transaction Mutation
export function useAddTransaction(payslipId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: TransactionFormData) => {
            return new Promise((resolve, reject) => {
                router.post(`/payslips/${payslipId}/transactions`, data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payslipKeys.detail(payslipId) });
        },
    });
}

// Remove Transaction Mutation
export function useRemoveTransaction(payslipId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (transactionId: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/payslips/${payslipId}/transactions/${transactionId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payslipKeys.detail(payslipId) });
        },
    });
}

// Finalize Payslip Mutation
export function useFinalizePayslip(payslipId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => {
            return new Promise((resolve, reject) => {
                router.post(`/payslips/${payslipId}/finalize`, {}, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payslipKeys.detail(payslipId) });
            queryClient.invalidateQueries({ queryKey: payslipKeys.all });
        },
    });
}

// Distribute Payslip Mutation
export function useDistributePayslip(payslipId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: DistributeFormData) => {
            return new Promise((resolve, reject) => {
                router.post(`/payslips/${payslipId}/distribute`, data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payslipKeys.detail(payslipId) });
            queryClient.invalidateQueries({ queryKey: payslipKeys.all });
        },
    });
}

// Delete Payslip Mutation
export function useDeletePayslip() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payslipId: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/payslips/${payslipId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: payslipKeys.all });
        },
    });
}
