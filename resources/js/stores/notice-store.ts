import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import type { Notice } from '@/hooks/queries/use-notices';

interface NoticeFilter {
    search?: string;
}

interface NoticeForm {
    notice_title: string;
    attach_file: File | null;
}

interface NoticeState {
    // Filters
    filters: NoticeFilter;
    setFilters: (filters: Partial<NoticeFilter>) => void;
    clearFilters: () => void;

    // Selected notice
    selectedNotice: Notice | null;
    setSelectedNotice: (notice: Notice | null) => void;

    // Form state
    form: NoticeForm;
    setForm: (form: Partial<NoticeForm>) => void;
    resetForm: () => void;

    // Modal/Dialog state
    showCreateDialog: boolean;
    setShowCreateDialog: (show: boolean) => void;

    showEditDialog: boolean;
    setShowEditDialog: (show: boolean) => void;

    showDeleteDialog: boolean;
    setShowDeleteDialog: (show: boolean) => void;

    showViewDialog: boolean;
    setShowViewDialog: (show: boolean) => void;

    // File preview
    filePreview: string | null;
    setFilePreview: (preview: string | null) => void;

    // Upload progress
    uploadProgress: number;
    setUploadProgress: (progress: number) => void;

    // Reset all state
    reset: () => void;
}

const initialForm: NoticeForm = {
    notice_title: '',
    attach_file: null,
};

const initialState = {
    filters: {},
    selectedNotice: null,
    form: initialForm,
    showCreateDialog: false,
    showEditDialog: false,
    showDeleteDialog: false,
    showViewDialog: false,
    filePreview: null,
    uploadProgress: 0,
};

export const useNoticeStore = create<NoticeState>()(
    devtools(
        (set, get) => ({
            ...initialState,

            // Filters
            setFilters: (newFilters) =>
                set((state) => ({
                    filters: { ...state.filters, ...newFilters },
                })),

            clearFilters: () => set({ filters: {} }),

            // Selected notice
            setSelectedNotice: (notice) => set({ selectedNotice: notice }),

            // Form state
            setForm: (newForm) =>
                set((state) => ({
                    form: { ...state.form, ...newForm },
                })),

            resetForm: () => set({ form: initialForm, filePreview: null }),

            // Dialog state
            setShowCreateDialog: (show) =>
                set({
                    showCreateDialog: show,
                    form: show ? initialForm : get().form,
                    filePreview: show ? null : get().filePreview,
                }),

            setShowEditDialog: (show) =>
                set((state) => {
                    if (show && state.selectedNotice) {
                        // Populate form with selected notice data
                        return {
                            showEditDialog: show,
                            form: {
                                notice_title: state.selectedNotice.notice_title,
                                attach_file: null,
                            },
                        };
                    }
                    return { showEditDialog: show };
                }),

            setShowDeleteDialog: (show) => set({ showDeleteDialog: show }),

            setShowViewDialog: (show) => set({ showViewDialog: show }),

            // File preview
            setFilePreview: (preview) => set({ filePreview: preview }),

            // Upload progress
            setUploadProgress: (progress) => set({ uploadProgress: progress }),

            // Reset
            reset: () => set(initialState),
        }),
        { name: 'NoticeStore' }
    )
);
