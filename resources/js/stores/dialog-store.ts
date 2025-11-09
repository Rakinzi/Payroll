import { create } from 'zustand';

export interface DialogConfig {
    id: string;
    type: 'alert' | 'confirm';
    title?: string;
    message: string;
    variant?: 'default' | 'destructive';
    confirmText?: string;
    cancelText?: string;
    onConfirm?: () => void | Promise<void>;
    onCancel?: () => void;
}

interface DialogState {
    dialogs: DialogConfig[];
    showAlert: (message: string, title?: string, variant?: 'default' | 'destructive') => void;
    showConfirm: (
        message: string,
        options?: {
            title?: string;
            confirmText?: string;
            cancelText?: string;
            variant?: 'default' | 'destructive';
            onConfirm?: () => void | Promise<void>;
            onCancel?: () => void;
        }
    ) => Promise<boolean>;
    closeDialog: (id: string) => void;
    closeAll: () => void;
}

export const useDialogStore = create<DialogState>()((set, get) => ({
    dialogs: [],

    showAlert: (message, title, variant = 'default') => {
        const id = crypto.randomUUID();
        set((state) => ({
            dialogs: [
                ...state.dialogs,
                {
                    id,
                    type: 'alert',
                    message,
                    title,
                    variant,
                    confirmText: 'OK',
                },
            ],
        }));
    },

    showConfirm: (message, options = {}) => {
        return new Promise<boolean>((resolve) => {
            const id = crypto.randomUUID();
            const dialog: DialogConfig = {
                id,
                type: 'confirm',
                message,
                title: options.title,
                variant: options.variant || 'default',
                confirmText: options.confirmText || 'Confirm',
                cancelText: options.cancelText || 'Cancel',
                onConfirm: async () => {
                    if (options.onConfirm) {
                        await options.onConfirm();
                    }
                    get().closeDialog(id);
                    resolve(true);
                },
                onCancel: () => {
                    if (options.onCancel) {
                        options.onCancel();
                    }
                    get().closeDialog(id);
                    resolve(false);
                },
            };

            set((state) => ({
                dialogs: [...state.dialogs, dialog],
            }));
        });
    },

    closeDialog: (id) => {
        set((state) => ({
            dialogs: state.dialogs.filter((dialog) => dialog.id !== id),
        }));
    },

    closeAll: () => {
        set({ dialogs: [] });
    },
}));
