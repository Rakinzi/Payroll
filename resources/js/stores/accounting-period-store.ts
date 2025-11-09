import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import type { AccountingPeriod } from '@/hooks/queries/use-accounting-periods';

interface ProcessingPeriod {
    periodId: number;
    action: 'run' | 'refresh' | 'close';
    startedAt: Date;
}

interface PeriodFilter {
    payroll_id?: string;
    year?: number;
    status?: 'Current' | 'Future' | 'Past' | 'All';
    search?: string;
}

interface AccountingPeriodState {
    // Current filters
    filters: PeriodFilter;
    setFilters: (filters: Partial<PeriodFilter>) => void;
    clearFilters: () => void;

    // Selected period for operations
    selectedPeriod: AccountingPeriod | null;
    setSelectedPeriod: (period: AccountingPeriod | null) => void;

    // Processing state
    processingPeriods: Map<number, ProcessingPeriod>;
    startProcessing: (periodId: number, action: 'run' | 'refresh' | 'close') => void;
    stopProcessing: (periodId: number) => void;
    isProcessing: (periodId: number) => boolean;
    getProcessingAction: (periodId: number) => string | null;

    // Currency selection
    periodCurrencies: Map<number, 'ZWG' | 'USD' | 'DEFAULT'>;
    setPeriodCurrency: (periodId: number, currency: 'ZWG' | 'USD' | 'DEFAULT') => void;
    getPeriodCurrency: (periodId: number) => 'ZWG' | 'USD' | 'DEFAULT';

    // Modal/Dialog state
    showGeneratePeriodDialog: boolean;
    setShowGeneratePeriodDialog: (show: boolean) => void;

    showPeriodDetailDialog: boolean;
    setShowPeriodDetailDialog: (show: boolean) => void;

    // Confirmation dialogs
    confirmationDialog: {
        isOpen: boolean;
        periodId: number | null;
        action: 'run' | 'refresh' | 'close' | null;
        currency?: 'ZWG' | 'USD' | 'DEFAULT';
    };
    openConfirmationDialog: (
        periodId: number,
        action: 'run' | 'refresh' | 'close',
        currency?: 'ZWG' | 'USD' | 'DEFAULT'
    ) => void;
    closeConfirmationDialog: () => void;

    // Reset all state
    reset: () => void;
}

const initialState = {
    filters: {},
    selectedPeriod: null,
    processingPeriods: new Map(),
    periodCurrencies: new Map(),
    showGeneratePeriodDialog: false,
    showPeriodDetailDialog: false,
    confirmationDialog: {
        isOpen: false,
        periodId: null,
        action: null,
    },
};

export const useAccountingPeriodStore = create<AccountingPeriodState>()(
    devtools(
        (set, get) => ({
            ...initialState,

            // Filters
            setFilters: (newFilters) =>
                set((state) => ({
                    filters: { ...state.filters, ...newFilters },
                })),

            clearFilters: () => set({ filters: {} }),

            // Selected period
            setSelectedPeriod: (period) => set({ selectedPeriod: period }),

            // Processing state
            startProcessing: (periodId, action) =>
                set((state) => {
                    const newMap = new Map(state.processingPeriods);
                    newMap.set(periodId, {
                        periodId,
                        action,
                        startedAt: new Date(),
                    });
                    return { processingPeriods: newMap };
                }),

            stopProcessing: (periodId) =>
                set((state) => {
                    const newMap = new Map(state.processingPeriods);
                    newMap.delete(periodId);
                    return { processingPeriods: newMap };
                }),

            isProcessing: (periodId) => {
                return get().processingPeriods.has(periodId);
            },

            getProcessingAction: (periodId) => {
                const processing = get().processingPeriods.get(periodId);
                return processing ? processing.action : null;
            },

            // Currency selection
            setPeriodCurrency: (periodId, currency) =>
                set((state) => {
                    const newMap = new Map(state.periodCurrencies);
                    newMap.set(periodId, currency);
                    return { periodCurrencies: newMap };
                }),

            getPeriodCurrency: (periodId) => {
                return get().periodCurrencies.get(periodId) || 'DEFAULT';
            },

            // Dialog state
            setShowGeneratePeriodDialog: (show) =>
                set({ showGeneratePeriodDialog: show }),

            setShowPeriodDetailDialog: (show) =>
                set({ showPeriodDetailDialog: show }),

            // Confirmation dialogs
            openConfirmationDialog: (periodId, action, currency) =>
                set({
                    confirmationDialog: {
                        isOpen: true,
                        periodId,
                        action,
                        currency,
                    },
                }),

            closeConfirmationDialog: () =>
                set({
                    confirmationDialog: {
                        isOpen: false,
                        periodId: null,
                        action: null,
                    },
                }),

            // Reset
            reset: () => set(initialState),
        }),
        { name: 'AccountingPeriodStore' }
    )
);

// Selectors
export const selectIsProcessing = (periodId: number) => (state: AccountingPeriodState) =>
    state.isProcessing(periodId);

export const selectPeriodCurrency = (periodId: number) => (state: AccountingPeriodState) =>
    state.getPeriodCurrency(periodId);

export const selectProcessingAction = (periodId: number) => (state: AccountingPeriodState) =>
    state.getProcessingAction(periodId);
