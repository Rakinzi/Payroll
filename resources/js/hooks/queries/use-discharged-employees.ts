import { router } from '@inertiajs/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export interface DischargedEmployee {
    id: string;
    emp_system_id: string;
    firstname: string;
    surname: string;
    middlename?: string;
    is_ex: boolean;
    is_ex_by: string | null;
    is_ex_on: string | null;
    employment_status: string;
    discharge_notes: string | null;
    reinstated_date: string | null;
    days_since_discharge: number | null;
    cost_center?: {
        id: string;
        center_name: string;
        center_code: string;
    };
    created_at: string;
    updated_at: string;
}

export interface ReinstateEmployeeData {
    employment_status: string;
    reinstated_date: string;
}

export const DISCHARGE_REASONS = {
    END_CONTRACT: 'END CONTRACT',
    RESIGNED: 'RESIGNED',
    DISMISSED: 'DISMISSED',
    DECEASED: 'DECEASED',
    SUSPENDED: 'SUSPENDED',
} as const;

export const DISCHARGE_REASON_COLORS = {
    'END CONTRACT': 'default',
    'RESIGNED': 'secondary',
    'DISMISSED': 'destructive',
    'DECEASED': 'outline',
    'SUSPENDED': 'default',
} as const;

// Query keys factory
export const dischargedEmployeeKeys = {
    all: ['discharged-employees'] as const,
    lists: () => [...dischargedEmployeeKeys.all, 'list'] as const,
    details: () => [...dischargedEmployeeKeys.all, 'detail'] as const,
    detail: (id: string) => [...dischargedEmployeeKeys.details(), id] as const,
};

// Mutation for reinstating employee
export function useReinstateEmployee(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: ReinstateEmployeeData) => {
            return new Promise((resolve, reject) => {
                router.post(`/discharged-employees/${id}/reinstate`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: dischargedEmployeeKeys.lists() });
                        queryClient.invalidateQueries({ queryKey: dischargedEmployeeKeys.detail(id) });
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

// Mutation for permanently deleting discharged employee
export function useDeleteDischargedEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/discharged-employees/${id}`, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: dischargedEmployeeKeys.lists() });
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
