import { router } from '@inertiajs/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export interface Admin {
    id: string;
    name: string;
    email: string;
    center_id: string | null;
    employee_id: string | null;
    is_active: boolean;
    last_login_at: string | null;
    last_login_ip: string | null;
    is_super_admin: boolean;
    is_cost_center_admin: boolean;
    employee?: {
        id: string;
        firstname: string;
        surname: string;
        emp_system_id: string;
    };
    cost_center?: {
        id: string;
        center_name: string;
    };
    roles?: Array<{
        id: string;
        name: string;
    }>;
    created_at: string;
    updated_at: string;
}

export interface CostCenter {
    id: string;
    center_name: string;
    center_code: string;
}

export interface CreateAdminData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    center_id?: string;
    employee_id?: string;
}

export interface UpdateAdminData {
    name: string;
    email: string;
    center_id?: string;
    employee_id?: string;
    is_active: boolean;
}

export interface ResetPasswordData {
    password: string;
    password_confirmation: string;
}

// Query keys factory
export const adminKeys = {
    all: ['admins'] as const,
    lists: () => [...adminKeys.all, 'list'] as const,
    details: () => [...adminKeys.all, 'detail'] as const,
    detail: (id: string) => [...adminKeys.details(), id] as const,
};

// Mutation for creating admin
export function useCreateAdmin() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: CreateAdminData) => {
            return new Promise((resolve, reject) => {
                router.post('/admins', data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: adminKeys.lists() });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

// Mutation for updating admin
export function useUpdateAdmin(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: UpdateAdminData) => {
            return new Promise((resolve, reject) => {
                router.put(`/admins/${id}`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: adminKeys.lists() });
                        queryClient.invalidateQueries({ queryKey: adminKeys.detail(id) });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

// Mutation for deleting admin
export function useDeleteAdmin() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/admins/${id}`, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: adminKeys.lists() });
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

// Mutation for resetting admin password
export function useResetAdminPassword(id: string) {
    return useMutation({
        mutationFn: async (data: ResetPasswordData) => {
            return new Promise((resolve, reject) => {
                router.post(`/admins/${id}/reset-password`, data, {
                    onSuccess: () => {
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                });
            });
        },
    });
}

// Constants
export const ADMIN_TYPES = {
    SUPER: 'super',
    COST_CENTER: 'cost_center',
} as const;
