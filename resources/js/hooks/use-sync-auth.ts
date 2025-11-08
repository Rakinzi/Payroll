import { useAuthStore } from '@/stores/auth-store';
import { useTenantStore } from '@/stores/tenant-store';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Hook to sync Inertia.js auth props with Zustand stores
 *
 * Call this in your main layout components to ensure
 * Zustand stores stay in sync with Inertia props
 */
export function useSyncAuth() {
    const { auth, tenant } = usePage<any>().props;
    const setUser = useAuthStore((state) => state.setUser);
    const setTenant = useTenantStore((state) => state.setTenant);

    useEffect(() => {
        if (auth?.user) {
            setUser({
                id: auth.user.id,
                name: auth.user.name,
                email: auth.user.email,
                center_id: auth.user.center_id,
                is_super_admin: auth.user.is_super_admin || auth.user.center_id === null,
                employee: auth.user.employee,
                can: auth.user.can,
            });
        } else {
            setUser(null);
        }
    }, [auth?.user, setUser]);

    useEffect(() => {
        if (tenant) {
            setTenant({
                id: tenant.id,
                name: tenant.name || tenant.system_name,
                logo: tenant.logo,
                database: tenant.database || tenant.tenancy_db_name,
            });
        }
    }, [tenant, setTenant]);
}
