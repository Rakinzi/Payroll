import { useMutation, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';

// Types
export interface ProfileUpdateData {
    firstname: string;
    surname: string;
    nationality?: string;
    nat_id: string;
    gender: 'Male' | 'Female';
    dob: string;
    marital_status?: 'Single' | 'Married' | 'Divorced' | 'Widowed';
    home_address: string;
    city: string;
    country: string;
    phone_number: string;
    personal_email_address?: string;
    religion?: string;
    drivers_licence_id?: string;
    drivers_licence_class?: number;
    passport_id?: string;
    department_id: string;
    position_id: string;
    payment_method: 'Cash' | 'Cheque' | 'Transfer';
    payment_basis: 'Daily' | 'Weekly' | 'Monthly' | 'Yearly';
    title: 'Hon' | 'Dr' | 'Mr' | 'Mrs' | 'Ms' | 'Sir';
}

export interface BankDetailsUpdateData {
    bank_name: string;
    bank_account_number: string;
    bank_branch: string;
    bank_account_name: string;
}

export interface PasswordUpdateData {
    old_password: string;
    new_password: string;
    new_password_confirmation: string;
}

export interface AvatarUpdateData {
    avatar: File;
}

export interface SignatureUpdateData {
    signature: string;
}

// Query Keys
export const profileKeys = {
    all: ['profile'] as const,
    details: () => [...profileKeys.all, 'detail'] as const,
};

// Mutations
export function useUpdateProfile() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: ProfileUpdateData) => {
            return new Promise((resolve, reject) => {
                router.put('/settings/profile', data, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: profileKeys.all });
        },
    });
}

export function useUpdateAvatar() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: AvatarUpdateData) => {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('avatar', data.avatar);

                router.post('/settings/profile/avatar', formData, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: profileKeys.all });
        },
    });
}

export function useUpdateSignature() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: SignatureUpdateData) => {
            return new Promise((resolve, reject) => {
                router.post('/settings/profile/signature', data, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: profileKeys.all });
        },
    });
}

export function useUpdatePassword() {
    return useMutation({
        mutationFn: (data: PasswordUpdateData) => {
            return new Promise((resolve, reject) => {
                router.post('/settings/profile/password', data, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
    });
}

export function useUpdatePayslipPassword() {
    return useMutation({
        mutationFn: (data: PasswordUpdateData) => {
            return new Promise((resolve, reject) => {
                router.post('/settings/profile/payslip-password', data, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
    });
}

export function useUpdateBankDetails() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: BankDetailsUpdateData) => {
            return new Promise((resolve, reject) => {
                router.put('/settings/profile/bank-details', data, {
                    preserveState: true,
                    onSuccess: () => resolve(undefined),
                    onError: (errors) => reject(errors),
                });
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: profileKeys.all });
        },
    });
}
