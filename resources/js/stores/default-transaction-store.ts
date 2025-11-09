import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import type { DefaultTransactionInput } from '@/hooks/queries/use-default-transactions';

interface TransactionRow extends DefaultTransactionInput {
    id: string; // Temporary ID for tracking rows
}

interface DefaultTransactionState {
    // Transaction rows
    rows: TransactionRow[];
    addRow: () => void;
    removeRow: (id: string) => void;
    updateRow: (id: string, data: Partial<DefaultTransactionInput>) => void;
    clearRows: () => void;
    initializeRows: (count?: number) => void;

    // Form state
    isSubmitting: boolean;
    setIsSubmitting: (submitting: boolean) => void;

    // Validation
    validateRows: () => boolean;
    getValidRows: () => DefaultTransactionInput[];

    // Reset all state
    reset: () => void;
}

const createEmptyRow = (): TransactionRow => ({
    id: crypto.randomUUID(),
    code_id: 0,
    transaction_effect: '+',
    employee_amount: 0,
    employer_amount: 0,
    hours_worked: 0,
    transaction_currency: 'USD',
});

const initialState = {
    rows: [createEmptyRow()],
    isSubmitting: false,
};

export const useDefaultTransactionStore = create<DefaultTransactionState>()(
    devtools(
        (set, get) => ({
            ...initialState,

            // Row management
            addRow: () =>
                set((state) => ({
                    rows: [...state.rows, createEmptyRow()],
                })),

            removeRow: (id) =>
                set((state) => ({
                    rows: state.rows.filter((row) => row.id !== id),
                })),

            updateRow: (id, data) =>
                set((state) => ({
                    rows: state.rows.map((row) =>
                        row.id === id ? { ...row, ...data } : row
                    ),
                })),

            clearRows: () =>
                set({
                    rows: [createEmptyRow()],
                }),

            initializeRows: (count = 1) =>
                set({
                    rows: Array.from({ length: count }, createEmptyRow),
                }),

            // Form state
            setIsSubmitting: (submitting) =>
                set({ isSubmitting: submitting }),

            // Validation
            validateRows: () => {
                const rows = get().rows;

                // Check if at least one row exists
                if (rows.length === 0) {
                    return false;
                }

                // Check if all rows have required fields
                return rows.every(
                    (row) =>
                        row.code_id > 0 &&
                        row.employee_amount >= 0 &&
                        (row.employer_amount === undefined || row.employer_amount >= 0) &&
                        (row.hours_worked === undefined || row.hours_worked >= 0) &&
                        (row.transaction_effect === '+' || row.transaction_effect === '-') &&
                        (row.transaction_currency === 'ZWL' || row.transaction_currency === 'USD')
                );
            },

            getValidRows: () => {
                const rows = get().rows;

                // Filter out rows with no code selected
                return rows
                    .filter((row) => row.code_id > 0)
                    .map(({ id, ...rest }) => rest); // Remove temporary ID
            },

            // Reset
            reset: () => set(initialState),
        }),
        { name: 'DefaultTransactionStore' }
    )
);
