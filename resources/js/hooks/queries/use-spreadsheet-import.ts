import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';

// TypeScript Interfaces
export interface ImportSession {
    id: string;
    import_type: 'employees' | 'salaries' | 'transactions' | 'banking';
    file_name: string;
    total_rows: number;
    processed_rows: number;
    error_rows: number;
    status: 'uploaded' | 'processing' | 'preview' | 'completed' | 'failed';
    progress_percentage: number;
    success_percentage: number;
    has_errors: boolean;
    imported_by?: {
        name: string;
        email: string;
    };
    created_at: string;
    updated_at: string;
}

export interface ImportSessionDetails extends ImportSession {
    can_be_processed: boolean;
}

export interface ImportError {
    id: string;
    column_name: string | null;
    error_message: string;
    raw_value: string | null;
    error_location: string;
}

export interface ImportPreviewData {
    session: ImportSessionDetails;
    previewData: Record<string, any>[];
    errorsByRow: Record<number, ImportError[]>;
}

export interface UploadFileData {
    file: File;
    import_type: 'employees' | 'salaries' | 'transactions' | 'banking';
}

export interface ExportData {
    export_type: 'employees' | 'payroll' | 'transactions';
    filters?: Record<string, any>;
}

// Query Keys
export const importKeys = {
    all: ['spreadsheet-imports'] as const,
    lists: () => [...importKeys.all, 'list'] as const,
    list: (filters: string) => [...importKeys.lists(), { filters }] as const,
    details: () => [...importKeys.all, 'detail'] as const,
    detail: (id: string) => [...importKeys.details(), id] as const,
    status: (id: string) => [...importKeys.all, 'status', id] as const,
};

// Upload File Mutation
export function useUploadFile() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: UploadFileData) => {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('file', data.file);
                formData.append('import_type', data.import_type);

                router.post('/spreadsheet-import/upload', formData as any, {
                    preserveState: false,
                    preserveScroll: false,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: importKeys.all });
        },
    });
}

// Process Import Mutation
export function useProcessImport(sessionId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => {
            return new Promise((resolve, reject) => {
                router.post(`/spreadsheet-import/${sessionId}/process`, {}, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: importKeys.all });
            queryClient.invalidateQueries({ queryKey: importKeys.detail(sessionId) });
        },
    });
}

// Delete Session Mutation
export function useDeleteSession() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (sessionId: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/spreadsheet-import/${sessionId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: importKeys.all });
        },
    });
}

// Get Import Status Query (for polling)
export function useImportStatus(sessionId: string, enabled: boolean = true) {
    return useQuery({
        queryKey: importKeys.status(sessionId),
        queryFn: async () => {
            const response = await fetch(`/spreadsheet-import/${sessionId}/status`);
            if (!response.ok) {
                throw new Error('Failed to fetch import status');
            }
            return response.json() as Promise<{
                status: string;
                progress: number;
                processed_rows: number;
                error_rows: number;
                total_rows: number;
            }>;
        },
        enabled,
        refetchInterval: (query) => {
            const data = query.state.data;
            // Stop polling if completed or failed
            if (data && (data.status === 'completed' || data.status === 'failed' || data.status === 'preview')) {
                return false;
            }
            return 2000; // Poll every 2 seconds
        },
    });
}

// Export Data Mutation
export function useExportData() {
    return useMutation({
        mutationFn: async (data: ExportData) => {
            const response = await fetch('/spreadsheet-import/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error('Export failed');
            }

            // Download the file
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${data.export_type}_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        },
    });
}
