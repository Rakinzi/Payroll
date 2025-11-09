import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import type { Currency } from '@/hooks/queries/use-currencies';

interface CurrencyFilter {
    search?: string;
    status?: 'all' | 'active' | 'inactive';
}

interface CurrencyForm {
    code: string;
    name: string;
    symbol: string;
    exchange_rate: number;
    decimal_places: number;
    description: string;
}

interface CurrencyState {
    // Filters
    filters: CurrencyFilter;
    setFilters: (filters: Partial<CurrencyFilter>) => void;
    clearFilters: () => void;

    // Selected currency
    selectedCurrency: Currency | null;
    setSelectedCurrency: (currency: Currency | null) => void;

    // Form state
    form: CurrencyForm;
    setForm: (form: Partial<CurrencyForm>) => void;
    resetForm: () => void;
    isEditMode: boolean;
    setIsEditMode: (isEdit: boolean) => void;

    // Modal/Dialog state
    showCreateDialog: boolean;
    setShowCreateDialog: (show: boolean) => void;

    showEditDialog: boolean;
    setShowEditDialog: (show: boolean) => void;

    showDeleteDialog: boolean;
    setShowDeleteDialog: (show: boolean) => void;

    // Reset all state
    reset: () => void;
}

const initialForm: CurrencyForm = {
    code: '',
    name: '',
    symbol: '',
    exchange_rate: 1.0,
    decimal_places: 2,
    description: '',
};

const initialState = {
    filters: { status: 'all' as const },
    selectedCurrency: null,
    form: initialForm,
    isEditMode: false,
    showCreateDialog: false,
    showEditDialog: false,
    showDeleteDialog: false,
};

export const useCurrencyStore = create<CurrencyState>()(
    devtools(
        (set, get) => ({
            ...initialState,

            // Filters
            setFilters: (newFilters) =>
                set((state) => ({
                    filters: { ...state.filters, ...newFilters },
                })),

            clearFilters: () => set({ filters: { status: 'all' } }),

            // Selected currency
            setSelectedCurrency: (currency) => set({ selectedCurrency: currency }),

            // Form state
            setForm: (newForm) =>
                set((state) => ({
                    form: { ...state.form, ...newForm },
                })),

            resetForm: () => set({ form: initialForm }),

            setIsEditMode: (isEdit) => set({ isEditMode: isEdit }),

            // Dialog state
            setShowCreateDialog: (show) =>
                set({
                    showCreateDialog: show,
                    isEditMode: false,
                    form: show ? initialForm : get().form,
                }),

            setShowEditDialog: (show) =>
                set((state) => {
                    if (show && state.selectedCurrency) {
                        // Populate form with selected currency data
                        return {
                            showEditDialog: show,
                            isEditMode: true,
                            form: {
                                code: state.selectedCurrency.code,
                                name: state.selectedCurrency.name,
                                symbol: state.selectedCurrency.symbol,
                                exchange_rate: state.selectedCurrency.exchange_rate,
                                decimal_places: state.selectedCurrency.decimal_places,
                                description: state.selectedCurrency.description || '',
                            },
                        };
                    }
                    return { showEditDialog: show };
                }),

            setShowDeleteDialog: (show) => set({ showDeleteDialog: show }),

            // Reset
            reset: () => set(initialState),
        }),
        { name: 'CurrencyStore' }
    )
);
