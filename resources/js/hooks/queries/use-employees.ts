import { queryOptions, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from '@inertiajs/react';
import type {
    Department,
    Position,
    Occupation,
    Paypoint,
    CostCenter,
    NecGrade,
} from '@/types';

export interface Employee {
    id: string;
    emp_system_id: string;
    // Personal Information
    title?: string;
    firstname: string;
    surname: string;
    othername?: string;
    nationality?: string;
    nat_id?: string;
    nassa_number?: string;
    gender?: 'male' | 'female' | 'other';
    date_of_birth?: string;
    marital_status?: 'single' | 'married' | 'divorced' | 'widowed';
    // Contact
    home_address?: string;
    city?: string;
    country?: string;
    phone?: string;
    emp_email: string;
    personal_email_address?: string;
    // Identification
    passport?: string;
    driver_license?: string;
    // Employment
    hire_date?: string;
    department_id?: string;
    position_id?: string;
    occupation_id?: string;
    paypoint_id?: string;
    center_id: string;
    average_working_days?: number;
    working_hours?: number;
    payment_basis?: 'monthly' | 'hourly' | 'daily';
    payment_method?: 'bank_transfer' | 'cash' | 'cheque';
    // Compensation
    basic_salary?: number;
    basic_salary_usd?: number;
    leave_entitlement?: number;
    leave_accrual?: number;
    // Tax
    tax_directives?: string;
    disability_status?: boolean;
    dependents?: number;
    vehicle_engine_capacity?: number;
    // Currency
    zwg_percentage?: number;
    usd_percentage?: number;
    // NEC
    nec_grade_id?: string;
    // Role & Status
    emp_role?: string;
    is_active: boolean;
    is_ex: boolean;
    is_ex_on?: string;
    employment_status?: string;
    discharge_notes?: string;
    // Relationships
    department?: Department;
    position?: Position;
    occupation?: Occupation;
    paypoint?: Paypoint;
    cost_center?: CostCenter;
    nec_grade?: NecGrade;
    // Timestamps
    created_at: string;
    updated_at: string;
}

export interface EmployeeBankDetail {
    id: string;
    employee_id: string;
    bank_name: string;
    branch_name: string;
    branch_code?: string;
    account_number: string;
    account_name?: string;
    account_type: 'Current' | 'Savings' | 'FCA';
    account_currency: 'USD' | 'ZWG' | 'ZiG';
    capacity: number;
    is_default: boolean;
    is_active: boolean;
    masked_account?: string;
}

interface EmployeesResponse {
    data: Employee[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

interface EmployeeFilters {
    page?: number;
    per_page?: number;
    search?: string;
    center_id?: string;
    is_ex?: boolean;
}

// Query keys factory
export const employeeKeys = {
    all: ['employees'] as const,
    lists: () => [...employeeKeys.all, 'list'] as const,
    list: (filters: EmployeeFilters) => [...employeeKeys.lists(), filters] as const,
    details: () => [...employeeKeys.all, 'detail'] as const,
    detail: (id: string) => [...employeeKeys.details(), id] as const,
};

// Fetch function
async function fetchEmployees(filters: EmployeeFilters = {}): Promise<EmployeesResponse> {
    const params = new URLSearchParams();
    if (filters.page) params.append('page', filters.page.toString());
    if (filters.per_page) params.append('per_page', filters.per_page.toString());
    if (filters.search) params.append('search', filters.search);
    if (filters.center_id) params.append('center_id', filters.center_id);
    if (filters.is_ex !== undefined) params.append('is_ex', filters.is_ex.toString());

    const response = await fetch(`/api/employees?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch employees');
    }

    return response.json();
}

async function fetchEmployee(id: string): Promise<Employee> {
    const response = await fetch(`/api/employees/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to fetch employee');
    }

    return response.json();
}

// Query options factory
export function employeesQueryOptions(filters: EmployeeFilters = {}) {
    return queryOptions({
        queryKey: employeeKeys.list(filters),
        queryFn: () => fetchEmployees(filters),
    });
}

export function employeeQueryOptions(id: string) {
    return queryOptions({
        queryKey: employeeKeys.detail(id),
        queryFn: () => fetchEmployee(id),
        enabled: !!id,
    });
}

// Hooks
export function useEmployees(filters: EmployeeFilters = {}) {
    return useQuery(employeesQueryOptions(filters));
}

export function useEmployee(id: string) {
    return useQuery(employeeQueryOptions(id));
}

// Mutation for creating employee
export function useCreateEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<Employee>) => {
            const response = await fetch('/api/employees', {
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
                throw new Error(error.message || 'Failed to create employee');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
        },
    });
}

// Mutation for updating employee
export function useUpdateEmployee(id: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<Employee>) => {
            const response = await fetch(`/api/employees/${id}`, {
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
                throw new Error(error.message || 'Failed to update employee');
            }

            return response.json();
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
            queryClient.invalidateQueries({ queryKey: employeeKeys.detail(id) });
        },
    });
}

// Mutation for deleting employee
export function useDeleteEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (id: string) => {
            const response = await fetch(`/api/employees/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to delete employee');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
        },
    });
}

// Mutation for terminating employee
export function useTerminateEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async ({ employeeId, data }: { employeeId: string; data: { is_ex_on: string; employment_status: string; discharge_notes?: string } }) => {
            router.post(`/employees/${employeeId}/terminate`, data, {
                onSuccess: () => {
                    queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
                    queryClient.invalidateQueries({ queryKey: employeeKeys.detail(employeeId) });
                },
            });
        },
    });
}

// Mutation for restoring employee
export function useRestoreEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (employeeId: string) => {
            router.post(`/employees/${employeeId}/restore`, {}, {
                onSuccess: () => {
                    queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
                    queryClient.invalidateQueries({ queryKey: employeeKeys.detail(employeeId) });
                },
            });
        },
    });
}

// Bank Details Query Keys
export const bankDetailKeys = {
    forEmployee: (employeeId: string) => [...employeeKeys.detail(employeeId), 'bank-details'] as const,
};

// Bank Details Mutations
export function useCreateBankDetail(employeeId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<EmployeeBankDetail>) => {
            const response = await fetch(`/employees/${employeeId}/bank-details`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ...data, employee_id: employeeId }),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to create bank detail');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: bankDetailKeys.forEmployee(employeeId) });
        },
    });
}

export function useUpdateBankDetail(employeeId: string, bankDetailId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: Partial<EmployeeBankDetail>) => {
            const response = await fetch(`/employees/${employeeId}/bank-details/${bankDetailId}`, {
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
                throw new Error(error.message || 'Failed to update bank detail');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: bankDetailKeys.forEmployee(employeeId) });
        },
    });
}

export function useDeleteBankDetail(employeeId: string) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (bankDetailId: string) => {
            const response = await fetch(`/employees/${employeeId}/bank-details/${bankDetailId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to delete bank detail');
            }

            return response.json();
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: bankDetailKeys.forEmployee(employeeId) });
        },
    });
}
