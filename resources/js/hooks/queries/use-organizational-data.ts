import { queryOptions, useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

// Types
export interface Department {
    id: string;
    dept_name: string;
    description?: string;
    is_active: boolean;
}

export interface Position {
    id: string;
    position_name: string;
    description?: string;
    is_active: boolean;
}

export interface TransactionCode {
    id: string;
    code_number: string;
    code_name: string;
    code_category: 'Earning' | 'Deduction' | 'Contribution';
    is_benefit: boolean;
    code_amount?: number;
    minimum_threshold?: number;
    maximum_threshold?: number;
    code_percentage?: number;
    is_editable: boolean;
    is_active: boolean;
    description?: string;
}

export interface TaxBand {
    id: string;
    currency: 'USD' | 'ZWG';
    period: 'monthly' | 'annual';
    min_salary: number;
    max_salary?: number;
    tax_rate: number;
    tax_amount: number;
    is_active: boolean;
}

export interface NECGrade {
    id: string;
    grade_name: string;
    t_code_id: string;
    contribution: 'Amount' | 'Percentage';
    employee_contr_amount?: number;
    employer_contr_amount?: number;
    employee_contr_percentage?: number;
    employer_contr_percentage?: number;
    min_threshold?: number;
    max_threshold?: number;
    is_automatic: boolean;
    is_active: boolean;
    transaction_code?: TransactionCode;
}

export interface Industry {
    id: string;
    industry_name: string;
    description?: string;
    is_active: boolean;
}

export interface Occupation {
    id: string;
    occupation_name: string;
    description?: string;
    is_active: boolean;
}

export interface Paypoint {
    id: string;
    paypoint_name: string;
    location?: string;
    description?: string;
    is_active: boolean;
}

export interface Company {
    id: string;
    company_name: string;
    company_email_address?: string;
    phone_number?: string;
    telephone_number?: string;
    physical_address?: string;
    description?: string;
    logo?: string;
    is_active: boolean;
}

export interface TaxCredit {
    id: string;
    credit_name: string;
    credit_amount: number;
    currency: 'USD' | 'ZWG';
    period: 'monthly' | 'annual';
    description?: string;
    is_active: boolean;
}

export interface VehicleBenefitBand {
    id: string;
    engine_capacity_min: number;
    engine_capacity_max?: number;
    benefit_amount: number;
    currency: 'USD' | 'ZWG';
    period: 'monthly' | 'annual';
    description?: string;
    is_active: boolean;
}

export interface CompanyBankDetail {
    id: string;
    bank_name: string;
    account_name: string;
    account_number: string;
    branch_name?: string;
    branch_code?: string;
    swift_code?: string;
    currency: 'USD' | 'ZWG';
    is_primary: boolean;
    is_active: boolean;
    description?: string;
}

export interface OrganizationalData {
    company?: Company;
    departments: Department[];
    positions: Position[];
    transaction_codes: TransactionCode[];
    tax_bands: TaxBand[];
    tax_credits: TaxCredit[];
    nec_grades: NECGrade[];
    vehicle_benefit_bands: VehicleBenefitBand[];
    company_bank_details: CompanyBankDetail[];
    industries: Industry[];
    occupations: Occupation[];
    paypoints: Paypoint[];
}

// Query keys
export const organizationalKeys = {
    all: ['organizational'] as const,
    data: () => [...organizationalKeys.all, 'data'] as const,
    departments: () => [...organizationalKeys.all, 'departments'] as const,
    positions: () => [...organizationalKeys.all, 'positions'] as const,
    transactionCodes: () => [...organizationalKeys.all, 'transaction-codes'] as const,
    taxBands: () => [...organizationalKeys.all, 'tax-bands'] as const,
    necGrades: () => [...organizationalKeys.all, 'nec-grades'] as const,
};

// Fetch function
async function fetchOrganizationalData(): Promise<OrganizationalData> {
    const response = await fetch('/api/organizational-data', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch organizational data');
    }

    return response.json();
}

// Query options
export function organizationalDataQueryOptions() {
    return queryOptions({
        queryKey: organizationalKeys.data(),
        queryFn: fetchOrganizationalData,
        staleTime: 1000 * 60 * 10, // 10 minutes
    });
}

// Main hook
export function useOrganizationalData() {
    return useQuery(organizationalDataQueryOptions());
}

// Specific hooks for each type
export function useDepartments() {
    const { data } = useOrganizationalData();
    return data?.departments ?? [];
}

export function usePositions() {
    const { data } = useOrganizationalData();
    return data?.positions ?? [];
}

export function useTransactionCodes() {
    const { data } = useOrganizationalData();
    return data?.transaction_codes ?? [];
}

export function useTaxBands() {
    const { data } = useOrganizationalData();
    return data?.tax_bands ?? [];
}

export function useNECGrades() {
    const { data } = useOrganizationalData();
    return data?.nec_grades ?? [];
}

export function useIndustries() {
    const { data } = useOrganizationalData();
    return data?.industries ?? [];
}

export function useOccupations() {
    const { data } = useOrganizationalData();
    return data?.occupations ?? [];
}

export function usePaypoints() {
    const { data } = useOrganizationalData();
    return data?.paypoints ?? [];
}

export function useCompany() {
    const { data } = useOrganizationalData();
    return data?.company ?? null;
}

export function useTaxCredits() {
    const { data } = useOrganizationalData();
    return data?.tax_credits ?? [];
}

export function useVehicleBenefitBands() {
    const { data } = useOrganizationalData();
    return data?.vehicle_benefit_bands ?? [];
}

export function useCompanyBankDetails() {
    const { data } = useOrganizationalData();
    return data?.company_bank_details ?? [];
}

// Generic CRUD mutations
export function useCreateOrganizationalItem(type: string, endpoint: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: any) => {
            const response = await fetch(endpoint, {
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
                throw new Error(error.message || `Failed to create ${type}`);
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: organizationalKeys.data() });
        },
    });
}

export function useUpdateOrganizationalItem(type: string, endpoint: string, id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: any) => {
            const response = await fetch(`${endpoint}/${id}`, {
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
                throw new Error(error.message || `Failed to update ${type}`);
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: organizationalKeys.data() });
        },
    });
}

export function useDeleteOrganizationalItem(type: string, endpoint: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            const response = await fetch(`${endpoint}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || `Failed to delete ${type}`);
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: organizationalKeys.data() });
        },
    });
}
