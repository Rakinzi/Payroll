import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    items?: NavItem[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

// Pagination types
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

export interface PaginatedData<T> {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
}

// Organizational data types
export interface Department {
    id: string;
    department_name: string;
    department_code?: string;
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Position {
    id: string;
    position_name: string;
    position_code?: string;
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Occupation {
    id: string;
    occupation_name: string;
    occupation_code?: string;
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Paypoint {
    id: string;
    paypoint_name: string;
    paypoint_code?: string;
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface CostCenter {
    id: string;
    cost_center_name: string;
    cost_center_code?: string;
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface NecGrade {
    id: string;
    grade_name: string;
    grade_code?: string;
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}
