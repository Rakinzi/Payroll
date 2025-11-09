import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import type { CompanyWorkingPolicy, LeaveBreakdown } from '@/utils/leave-calculation';

interface CalculateRequest {
    start_date: string;
    end_date: string;
    working_days_policy?: '5_day' | '6_day' | '7_day';
    exclude_saturdays?: boolean;
    exclude_sundays?: boolean;
    exclude_public_holidays?: boolean;
    custom_holidays?: string[];
}

interface CalculateResponse {
    working_days: number;
    start_date: string;
    end_date: string;
    policy: CompanyWorkingPolicy;
}

interface BreakdownResponse {
    breakdown: LeaveBreakdown;
    start_date: string;
    end_date: string;
    policy: CompanyWorkingPolicy;
}

/**
 * Hook to calculate working days via API
 */
export function useCalculateWorkingDays() {
    return useMutation({
        mutationFn: async (data: CalculateRequest): Promise<CalculateResponse> => {
            const response = await axios.post('/leave-calculation/calculate', data);
            return response.data;
        },
    });
}

/**
 * Hook to get detailed leave breakdown via API
 */
export function useLeaveBreakdown() {
    return useMutation({
        mutationFn: async (data: CalculateRequest): Promise<BreakdownResponse> => {
            const response = await axios.post('/leave-calculation/breakdown', data);
            return response.data;
        },
    });
}
