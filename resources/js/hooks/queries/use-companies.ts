import { router } from '@inertiajs/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

export interface Company {
    id: string;
    company_name: string;
    company_email_address: string;
    phone_number: string;
    telephone_number: string | null;
    physical_address: string;
    registration_number: string | null;
    tax_number: string | null;
    industry: string | null;
    website: string | null;
    description: string | null;
    logo: string | null;
    logo_url: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface CompanyData {
    company_name: string;
    company_email_address: string;
    phone_number: string;
    telephone_number?: string | null;
    physical_address: string;
    registration_number?: string | null;
    tax_number?: string | null;
    industry?: string | null;
    website?: string | null;
    description?: string | null;
    is_active?: boolean;
}

// Query keys factory
export const companyKeys = {
    all: ['companies'] as const,
    detail: () => [...companyKeys.all, 'detail'] as const,
};

// Mutation for updating company
export function useUpdateCompany(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: CompanyData) => {
            return new Promise((resolve, reject) => {
                router.put(`/companies/${id}`, data, {
                    onSuccess: () => {
                        queryClient.invalidateQueries({ queryKey: companyKeys.all });
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

// Mutation for uploading company logo
export function useUploadCompanyLogo(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (logo: File) => {
            const formData = new FormData();
            formData.append('logo', logo);

            return fetch(`/companies/${id}/logo`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            }).then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to upload logo');
                }
                return response.json();
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: companyKeys.all });
        },
    });
}

// Mutation for deleting company logo
export function useDeleteCompanyLogo(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async () => {
            return fetch(`/companies/${id}/logo`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            }).then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to delete logo');
                }
                return response.json();
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: companyKeys.all });
        },
    });
}
