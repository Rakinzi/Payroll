import { useDialogStore } from '@/stores/dialog-store';
import { useCallback } from 'react';

/**
 * Hook to show alert and confirm dialogs
 * Replaces native JavaScript alert() and confirm()
 */
export function useDialog() {
    const showAlert = useDialogStore((state) => state.showAlert);
    const showConfirm = useDialogStore((state) => state.showConfirm);

    /**
     * Show an alert dialog
     * Replacement for window.alert()
     *
     * @param message - The message to display
     * @param title - Optional title for the dialog
     * @param variant - 'default' or 'destructive' styling
     */
    const alert = useCallback(
        (message: string, title?: string, variant?: 'default' | 'destructive') => {
            showAlert(message, title, variant);
        },
        [showAlert]
    );

    /**
     * Show a confirmation dialog
     * Replacement for window.confirm()
     *
     * @param message - The message to display
     * @param options - Configuration options
     * @returns Promise<boolean> - true if confirmed, false if cancelled
     */
    const confirm = useCallback(
        (
            message: string,
            options?: {
                title?: string;
                confirmText?: string;
                cancelText?: string;
                variant?: 'default' | 'destructive';
                onConfirm?: () => void | Promise<void>;
                onCancel?: () => void;
            }
        ): Promise<boolean> => {
            return showConfirm(message, options);
        },
        [showConfirm]
    );

    return {
        alert,
        confirm,
    };
}
