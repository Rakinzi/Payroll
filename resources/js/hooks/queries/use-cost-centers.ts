import { queryOptions, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

interface CostCenter {
    id: string;
    center_name: string;
    center_code: string;
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

// Query keys factory
export const costCenterKeys = {
    all: ['cost-centers'] as const,
    lists: () => [...costCenterKeys.all, 'list'] as const,
    list: (filters?: { active?: boolean }) => [...costCenterKeys.lists(), filters] as const,
    details: () => [...costCenterKeys.all, 'detail'] as const,
    detail: (id: string) => [...costCenterKeys.details(), id] as const,
};

// Fetch functions
async function fetchCostCenters(filters?: { active?: boolean }): Promise<CostCenter[]> {
    const params = new URLSearchParams();
    if (filters?.active !== undefined) params.append('active', filters.active.toString());

    const response = await fetch(`/api/cost-centers?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch cost centers');
    }

    return response.json();
}

async function fetchCostCenter(id: string): Promise<CostCenter> {
    const response = await fetch(`/api/cost-centers/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch cost center');
    }

    return response.json();
}

// Query options factory
export function costCentersQueryOptions(filters?: { active?: boolean }) {
    return queryOptions({
        queryKey: costCenterKeys.list(filters),
        queryFn: () => fetchCostCenters(filters),
    });
}

export function costCenterQueryOptions(id: string) {
    return queryOptions({
        queryKey: costCenterKeys.detail(id),
        queryFn: () => fetchCostCenter(id),
        enabled: !!id,
    });
}

// Hooks
export function useCostCenters(filters?: { active?: boolean }) {
    return useQuery(costCentersQueryOptions(filters));
}

export function useCostCenter(id: string) {
    return useQuery(costCenterQueryOptions(id));
}

// Mutation for creating cost center
export function useCreateCostCenter() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<CostCenter>) => {
            const response = await fetch('/api/cost-centers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to create cost center');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: costCenterKeys.lists() });
        },
    });
}

// Mutation for updating cost center
export function useUpdateCostCenter(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<CostCenter>) => {
            const response = await fetch(`/api/cost-centers/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to update cost center');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: costCenterKeys.lists() });
            queryClient.invalidateQueries({ queryKey: costCenterKeys.detail(id) });
        },
    });
}

// Mutation for deleting cost center
export function useDeleteCostCenter() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            const response = await fetch(`/api/cost-centers/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to delete cost center');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: costCenterKeys.lists() });
        },
    });
}
