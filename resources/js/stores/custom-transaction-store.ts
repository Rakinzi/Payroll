import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import type { CustomTransaction, CustomTransactionInput } from '@/hooks/queries/use-custom-transactions';

interface TransactionFilter {
    payroll_id?: string;
    period_id?: number;
}

interface CustomTransactionForm {
    period_id: number;
    worked_hours: number;
    base_hours: number;
    base_amount: number;
    use_basic: boolean;
    employees: string[];
    transaction_codes: number[];
}

interface CustomTransactionState {
    // Filters
    filters: TransactionFilter;
    setFilters: (filters: Partial<TransactionFilter>) => void;
    clearFilters: () => void;

    // Selected transaction
    selectedTransaction: CustomTransaction | null;
    setSelectedTransaction: (transaction: CustomTransaction | null) => void;

    // Form state
    form: CustomTransactionForm;
    setForm: (form: Partial<CustomTransactionForm>) => void;
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

    // View details dialog
    showDetailsDialog: boolean;
    setShowDetailsDialog: (show: boolean) => void;

    // Calculated estimate
    estimate: any | null;
    setEstimate: (estimate: any | null) => void;

    // Reset all state
    reset: () => void;
}

const initialForm: CustomTransactionForm = {
    period_id: 0,
    worked_hours: 0,
    base_hours: 176,
    base_amount: 0,
    use_basic: true,
    employees: [],
    transaction_codes: [],
};

const initialState = {
    filters: {},
    selectedTransaction: null,
    form: initialForm,
    isEditMode: false,
    showCreateDialog: false,
    showEditDialog: false,
    showDeleteDialog: false,
    showDetailsDialog: false,
    estimate: null,
};

export const useCustomTransactionStore = create<CustomTransactionState>()(
    devtools(
        (set, get) => ({
            ...initialState,

            // Filters
            setFilters: (newFilters) =>
                set((state) => ({
                    filters: { ...state.filters, ...newFilters },
                })),

            clearFilters: () => set({ filters: {} }),

            // Selected transaction
            setSelectedTransaction: (transaction) =>
                set({ selectedTransaction: transaction }),

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
                    if (show && state.selectedTransaction) {
                        // Populate form with selected transaction data
                        return {
                            showEditDialog: show,
                            isEditMode: true,
                            form: {
                                period_id: state.selectedTransaction.period_id,
                                worked_hours: state.selectedTransaction.worked_hours,
                                base_hours: state.selectedTransaction.base_hours,
                                base_amount: state.selectedTransaction.base_amount || 0,
                                use_basic: state.selectedTransaction.use_basic,
                                employees: state.selectedTransaction.employees?.map((e) => e.id) || [],
                                transaction_codes: state.selectedTransaction.transaction_codes?.map((c) => c.code_id) || [],
                            },
                        };
                    }
                    return { showEditDialog: show };
                }),

            setShowDeleteDialog: (show) => set({ showDeleteDialog: show }),

            setShowDetailsDialog: (show) => set({ showDetailsDialog: show }),

            // Estimate
            setEstimate: (estimate) => set({ estimate }),

            // Reset
            reset: () => set(initialState),
        }),
        { name: 'CustomTransactionStore' }
    )
);
