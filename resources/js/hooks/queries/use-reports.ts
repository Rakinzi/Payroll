import { useMutation, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';

// TypeScript Interfaces
export interface Payroll {
    id: string;
    payroll_name: string;
}

export interface ReportBase {
    id: string;
    payroll: Payroll;
    generated_at: string;
}

export interface CostAnalysisFormData {
    payroll_id: string;
    report_type: 'department' | 'designation' | 'codes' | 'leave';
    period_start: string;
    period_end: string;
    currency: 'ZWG' | 'USD';
}

export interface ItfFormData {
    payroll_id: string;
    form_type: 'ITF16' | 'ITF36';
    tax_year: number;
    currency: 'ZWG' | 'USD';
}

export interface VarianceAnalysisFormData {
    payroll_id: string;
    analysis_type: 'summary' | 'detailed';
    baseline_period: string;
    comparison_period: string;
}

export interface ThirdPartyReportFormData {
    payroll_id: string;
    report_type: 'standard_levy' | 'zimdef' | 'zimra_p2';
    period_start: string;
    period_end: string;
    currency: 'ZWG' | 'USD';
}

export interface ScheduledReportFormData {
    payroll_id: string;
    report_type: string;
    frequency: 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly';
    email_recipients?: string;
    parameters?: Record<string, any>;
}

export interface TaxableAccumulativeFormData {
    payroll_id: string;
    tax_year: number;
    currency: 'ZWG' | 'USD';
}

export interface TaxCellAccumulativeFormData {
    payroll_id: string;
    tax_year: number;
    currency: 'ZWG' | 'USD';
}

export interface RetirementWarningFormData {
    payroll_id: string;
    warning_threshold_months: number;
}

export interface EmployeeRequisitionFormData {
    payroll_id: string;
    period_start: string;
    period_end: string;
}

// Query Keys
export const reportKeys = {
    all: ['reports'] as const,
    lists: () => [...reportKeys.all, 'list'] as const,
    list: (filters: string) => [...reportKeys.lists(), { filters }] as const,
    details: () => [...reportKeys.all, 'detail'] as const,
    detail: (id: string) => [...reportKeys.details(), id] as const,
};

// Generate Cost Analysis Report
export function useGenerateCostAnalysis() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: CostAnalysisFormData) => {
            const response = await fetch('/reports/cost-analysis/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Failed to generate report');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Generate ITF Form
export function useGenerateItfForm() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: ItfFormData) => {
            const response = await fetch('/reports/itf-forms/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Failed to generate ITF form');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Generate Variance Analysis
export function useGenerateVarianceAnalysis() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: VarianceAnalysisFormData) => {
            const response = await fetch('/reports/variance-analysis/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Failed to generate variance analysis');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Generate Third-Party Report
export function useGenerateThirdPartyReport() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: ThirdPartyReportFormData) => {
            const response = await fetch('/reports/third-party/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Failed to generate third-party report');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Submit Third-Party Report
export function useSubmitThirdPartyReport(reportId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => {
            return new Promise((resolve, reject) => {
                router.post(`/reports/third-party/${reportId}/submit`, {}, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Create Scheduled Report
export function useCreateScheduledReport() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: ScheduledReportFormData) => {
            return new Promise((resolve, reject) => {
                router.post('/reports/scheduled/create', data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Delete Scheduled Report
export function useDeleteScheduledReport() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (scheduleId: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/reports/scheduled/${scheduleId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Generate Taxable Accumulatives
export function useGenerateTaxableAccumulatives() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: TaxableAccumulativeFormData) => {
            const response = await fetch('/reports/taxable-accumulatives/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Failed to generate taxable accumulatives report');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Generate Tax Cell Accumulatives
export function useGenerateTaxCellAccumulatives() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: TaxCellAccumulativeFormData) => {
            const response = await fetch('/reports/tax-cell-accumulatives/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Failed to generate tax cell accumulatives report');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Generate Retirement Warning
export function useGenerateRetirementWarning() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: RetirementWarningFormData) => {
            const response = await fetch('/reports/retirement-warning/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Failed to generate retirement warning report');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}

// Generate Employee Requisition
export function useGenerateEmployeeRequisition() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: EmployeeRequisitionFormData) => {
            const response = await fetch('/reports/employee-requisition/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Failed to generate employee requisition report');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: reportKeys.all });
        },
    });
}
