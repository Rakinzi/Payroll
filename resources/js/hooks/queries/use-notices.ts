import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';
import axios from 'axios';

// Types
export interface Notice {
    notice_id: number;
    employee_id: string;
    notice_title: string;
    file_name: string;
    file_size: number;
    file_size_formatted: string;
    file_extension: string;
    is_image: boolean;
    is_document: boolean;
    is_spreadsheet: boolean;
    created_at: string;
    updated_at: string;
    uploader?: {
        id: string;
        firstname: string;
        surname: string;
    };
    can_modify: boolean;
}

export interface NoticeFormData {
    notice_title: string;
    attach_file: File;
}

export interface NoticeUpdateData {
    notice_title: string;
    attach_file?: File;
}

// Query Keys
export const noticeKeys = {
    all: ['notices'] as const,
    lists: () => [...noticeKeys.all, 'list'] as const,
    list: (filters: string) => [...noticeKeys.lists(), { filters }] as const,
    details: () => [...noticeKeys.all, 'detail'] as const,
    detail: (id: number) => [...noticeKeys.details(), id] as const,
    latest: () => [...noticeKeys.all, 'latest'] as const,
};

// Queries
export function useNoticeDetails(noticeId: number, enabled: boolean = true) {
    return useQuery({
        queryKey: noticeKeys.detail(noticeId),
        queryFn: async () => {
            const response = await axios.get(`/notices/${noticeId}`);
            return response.data;
        },
        enabled,
    });
}

export function useLatestNotices(limit: number = 5) {
    return useQuery({
        queryKey: [...noticeKeys.latest(), limit],
        queryFn: async () => {
            const response = await axios.get('/notices/latest/all', {
                params: { limit },
            });
            return response.data.notices as Notice[];
        },
    });
}

// Mutations
export function useCreateNotice() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: NoticeFormData) => {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('notice_title', data.notice_title);
                formData.append('attach_file', data.attach_file);

                router.post('/notices', formData, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: noticeKeys.all });
        },
    });
}

export function useUpdateNotice(noticeId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: NoticeUpdateData) => {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('notice_title', data.notice_title);
                formData.append('_method', 'PUT');

                if (data.attach_file) {
                    formData.append('attach_file', data.attach_file);
                }

                router.post(`/notices/${noticeId}`, formData, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: noticeKeys.all });
            queryClient.invalidateQueries({ queryKey: noticeKeys.detail(noticeId) });
        },
    });
}

export function useDeleteNotice() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (noticeId: number) => {
            return new Promise((resolve, reject) => {
                router.delete(`/notices/${noticeId}`, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: noticeKeys.all });
        },
    });
}

export function downloadNotice(noticeId: number) {
    window.location.href = `/notices/${noticeId}/download`;
}
