import { create } from 'zustand';
import { devtools } from 'zustand/middleware';

interface Tenant {
    id: string;
    name: string;
    logo?: string;
    database: string;
}

interface CostCenter {
    id: string;
    center_name: string;
    center_code: string;
    description?: string;
    is_active: boolean;
}

interface TenantState {
    tenant: Tenant | null;
    setTenant: (tenant: Tenant | null) => void;

    currentCostCenter: CostCenter | null;
    setCurrentCostCenter: (costCenter: CostCenter | null) => void;

    availableCostCenters: CostCenter[];
    setAvailableCostCenters: (costCenters: CostCenter[]) => void;
}

export const useTenantStore = create<TenantState>()(
    devtools(
        (set) => ({
            tenant: null,
            setTenant: (tenant) => set({ tenant }),

            currentCostCenter: null,
            setCurrentCostCenter: (costCenter) => set({ currentCostCenter: costCenter }),

            availableCostCenters: [],
            setAvailableCostCenters: (costCenters) =>
                set({ availableCostCenters: costCenters }),
        }),
        { name: 'TenantStore' },
    ),
);
