import { router } from '@inertiajs/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export interface VehicleBenefitBand {
    id: string;
    engine_capacity_min: number;
    engine_capacity_max: number | null;
    benefit_amount: number;
    currency: 'USD' | 'ZWG';
    period: 'monthly' | 'annual';
    description: string | null;
    is_active: boolean;
    capacity_range: string;
    formatted_benefit_amount: string;
    created_at: string;
    updated_at: string;
}

export interface VehicleBenefitBandData {
    engine_capacity_min: number;
    engine_capacity_max: number | null;
    benefit_amount: number;
    currency: 'USD' | 'ZWG';
    period: 'monthly' | 'annual';
    description?: string | null;
    is_active: boolean;
}

export const CURRENCIES = ['USD', 'ZWG'] as const;
export type Currency = typeof CURRENCIES[number];

export const PERIODS = ['monthly', 'annual'] as const;
export type Period = typeof PERIODS[number];

// Query keys factory
export const vehicleBenefitKeys = {
    all: ['vehicle-benefits'] as const,
    lists: () => [...vehicleBenefitKeys.all, 'list'] as const,
    list: (filters: Record<string, any>) => [...vehicleBenefitKeys.lists(), filters] as const,
};

// Mutation for creating vehicle benefit band
export function useCreateVehicleBenefitBand() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: VehicleBenefitBandData) => {
            return new Promise((resolve, reject) => {
                router.post('/vehicle-benefits', data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: vehicleBenefitKeys.all });
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

// Mutation for updating vehicle benefit band
export function useUpdateVehicleBenefitBand(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: VehicleBenefitBandData) => {
            return new Promise((resolve, reject) => {
                router.put(`/vehicle-benefits/${id}`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: vehicleBenefitKeys.all });
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

// Mutation for deleting vehicle benefit band
export function useDeleteVehicleBenefitBand() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            return new Promise((resolve, reject) => {
                router.delete(`/vehicle-benefits/${id}`, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: vehicleBenefitKeys.all });
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
