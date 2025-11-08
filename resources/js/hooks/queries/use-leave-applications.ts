import { useMutation, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';

// TypeScript Interfaces
export interface Employee {
    id: string;
    emp_system_id: string;
    full_name: string;
    position?: {
        position_name: string;
    };
    department?: {
        department_name: string;
    };
}

export interface LeaveApplication {
    id: string;
    employee: Employee;
    admin?: {
        name: string;
    };
    leave_type: string;
    leave_source: string;
    date_from: string;
    date_to: string;
    total_days: number;
    comments: string | null;
    leave_type_color: string;
    created_at: string;
}

export interface LeaveApplicationFormData {
    employee_id: string;
    leave_type: string;
    leave_source: string;
    date_from: string;
    date_to: string;
    comments?: string;
}

// Query Keys
export const leaveApplicationKeys = {
    all: ['leave-applications'] as const,
    lists: () => [...leaveApplicationKeys.all, 'list'] as const,
    list: (filters: string) => [...leaveApplicationKeys.lists(), { filters }] as const,
    details: () => [...leaveApplicationKeys.all, 'detail'] as const,
    detail: (id: string) => [...leaveApplicationKeys.details(), id] as const,
};

// Create Leave Application Mutation
export function useCreateLeaveApplication() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: LeaveApplicationFormData) => {
            return new Promise((resolve, reject) => {
                router.post('/leave-applications', data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: leaveApplicationKeys.all });
        },
    });
}

// Update Leave Application Mutation
export function useUpdateLeaveApplication(leaveApplicationId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: LeaveApplicationFormData) => {
            return new Promise((resolve, reject) => {
                router.put(`/leave-applications/${leaveApplicationId}`, data, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: leaveApplicationKeys.all });
            queryClient.invalidateQueries({
                queryKey: leaveApplicationKeys.detail(leaveApplicationId),
            });
        },
    });
}

// Delete Leave Application Mutation
export function useDeleteLeaveApplication() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (leaveApplicationId: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/leave-applications/${leaveApplicationId}`, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: leaveApplicationKeys.all });
        },
    });
}
