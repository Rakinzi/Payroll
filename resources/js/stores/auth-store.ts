import { create } from 'zustand';
import { devtools } from 'zustand/middleware';

interface User {
    id: string;
    name: string;
    email: string;
    center_id: string | null;
    is_super_admin: boolean;
    employee?: {
        id: string;
        firstname: string;
        surname: string;
    };
    can?: Record<string, boolean>;
}

interface AuthState {
    user: User | null;
    setUser: (user: User | null) => void;
    updateUser: (updates: Partial<User>) => void;
    hasPermission: (permission: string) => boolean;
    hasAnyPermission: (permissions: string[]) => boolean;
    hasAllPermissions: (permissions: string[]) => boolean;
    isSuperAdmin: () => boolean;
}

export const useAuthStore = create<AuthState>()(
    devtools(
        (set, get) => ({
            user: null,

            setUser: (user) => set({ user }),

            updateUser: (updates) =>
                set((state) => ({
                    user: state.user ? { ...state.user, ...updates } : null,
                })),

            hasPermission: (permission) => {
                const { user } = get();
                if (!user) return false;
                if (user.is_super_admin) return true;
                return user.can?.[permission] ?? false;
            },

            hasAnyPermission: (permissions) => {
                const { hasPermission } = get();
                return permissions.some((permission) => hasPermission(permission));
            },

            hasAllPermissions: (permissions) => {
                const { hasPermission } = get();
                return permissions.every((permission) => hasPermission(permission));
            },

            isSuperAdmin: () => {
                const { user } = get();
                return user?.is_super_admin ?? false;
            },
        }),
        { name: 'AuthStore' },
    ),
);
