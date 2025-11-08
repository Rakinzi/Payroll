# State Management Guide

This application uses **Zustand** for global state management and **React Query (TanStack Query)** for server state management, replacing traditional `useState` and `useEffect` patterns.

## Table of Contents

- [Why Zustand + React Query?](#why-zustand--react-query)
- [Zustand Stores](#zustand-stores)
- [React Query Hooks](#react-query-hooks)
- [Examples](#examples)
- [Best Practices](#best-practices)

---

## Why Zustand + React Query?

### Problems with useState + useEffect

**Before:**
```tsx
function EmployeeList() {
    const [employees, setEmployees] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        setLoading(true);
        fetch('/api/employees')
            .then(res => res.json())
            .then(data => setEmployees(data))
            .catch(err => setError(err))
            .finally(() => setLoading(false));
    }, []);

    // Manual refetch logic, cache management, etc.
}
```

**After:**
```tsx
function EmployeeList() {
    const { data, isLoading, error } = useEmployees();
    // Automatic caching, refetching, background updates, etc.
}
```

### Benefits

1. **Less Boilerplate**: No manual loading/error states
2. **Automatic Caching**: Data cached automatically with smart invalidation
3. **Better Performance**: Deduplicates requests, background refetching
4. **DevTools**: Built-in debugging with React Query DevTools
5. **Predictable**: Zustand provides simple, predictable state updates
6. **TypeScript**: Full type safety out of the box

---

## Zustand Stores

### Available Stores

#### 1. UI Store (`useUIStore`)

Manages UI-related state like modals, notifications, sidebar, loading states.

```tsx
import { useUIStore } from '@/stores/ui-store';

function MyComponent() {
    // Select only what you need (prevents unnecessary re-renders)
    const sidebarOpen = useUIStore((state) => state.sidebarOpen);
    const toggleSidebar = useUIStore((state) => state.toggleSidebar);
    const addNotification = useUIStore((state) => state.addNotification);

    return (
        <button onClick={toggleSidebar}>
            Toggle Sidebar
        </button>
    );
}
```

**Available State:**
- `sidebarOpen` - Boolean for sidebar state
- `modals` - Array of open modals
- `notifications` - Array of notifications
- `globalLoading` - Global loading state

**Available Actions:**
- `toggleSidebar()` - Toggle sidebar
- `setSidebarOpen(open)` - Set sidebar state
- `openModal(modal)` - Open a modal
- `closeModal(id)` - Close specific modal
- `addNotification(notification)` - Show notification
- `removeNotification(id)` - Remove notification
- `setGlobalLoading(loading)` - Set global loading

#### 2. Auth Store (`useAuthStore`)

Manages authentication and permission state.

```tsx
import { useAuthStore } from '@/stores/auth-store';

function MyComponent() {
    const user = useAuthStore((state) => state.user);
    const hasPermission = useAuthStore((state) => state.hasPermission);

    if (!hasPermission('view employees')) {
        return <div>No permission</div>;
    }

    return <div>Welcome {user?.name}</div>;
}
```

**Available State:**
- `user` - Current user object

**Available Actions:**
- `setUser(user)` - Set current user
- `updateUser(updates)` - Partial update user
- `hasPermission(permission)` - Check single permission
- `hasAnyPermission(permissions)` - Check if has any permission
- `hasAllPermissions(permissions)` - Check if has all permissions
- `isSuperAdmin()` - Check if super admin

#### 3. Tenant Store (`useTenantStore`)

Manages tenant and cost center state.

```tsx
import { useTenantStore } from '@/stores/tenant-store';

function MyComponent() {
    const tenant = useTenantStore((state) => state.tenant);
    const currentCostCenter = useTenantStore((state) => state.currentCostCenter);

    return (
        <div>
            <p>Tenant: {tenant?.name}</p>
            <p>Cost Center: {currentCostCenter?.center_name}</p>
        </div>
    );
}
```

---

## React Query Hooks

### Employee Queries

```tsx
import { useEmployees, useEmployee, useCreateEmployee, useUpdateEmployee, useDeleteEmployee } from '@/hooks/queries/use-employees';

function EmployeeManagement() {
    // Fetch employees with filters
    const { data, isLoading, error, refetch } = useEmployees({
        page: 1,
        per_page: 10,
        search: 'John',
        center_id: 'abc-123',
        is_ex: false,
    });

    // Fetch single employee
    const { data: employee } = useEmployee('employee-id');

    // Create mutation
    const createMutation = useCreateEmployee();
    const handleCreate = async () => {
        await createMutation.mutateAsync({
            firstname: 'John',
            surname: 'Doe',
            // ...
        });
    };

    // Update mutation
    const updateMutation = useUpdateEmployee('employee-id');
    const handleUpdate = async () => {
        await updateMutation.mutateAsync({
            firstname: 'Jane',
        });
    };

    // Delete mutation
    const deleteMutation = useDeleteEmployee();
    const handleDelete = async () => {
        await deleteMutation.mutateAsync('employee-id');
    };

    return (
        <div>
            {isLoading && <p>Loading...</p>}
            {error && <p>Error: {error.message}</p>}
            {data?.data.map(emp => <div key={emp.id}>{emp.firstname}</div>)}
        </div>
    );
}
```

### Cost Center Queries

```tsx
import { useCostCenters, useCostCenter } from '@/hooks/queries/use-cost-centers';

function CostCenters() {
    // Fetch all active cost centers
    const { data: centers } = useCostCenters({ active: true });

    // Fetch single cost center
    const { data: center } = useCostCenter('center-id');

    return <div>{/* ... */}</div>;
}
```

### Custom Hooks

#### Notifications

```tsx
import { useNotification } from '@/hooks/use-notification';

function MyComponent() {
    const notification = useNotification();

    const handleAction = () => {
        notification.success('Action completed!', 'Success');
        notification.error('Something went wrong', 'Error');
        notification.warning('Be careful!', 'Warning');
        notification.info('FYI', 'Information');
    };

    return <button onClick={handleAction}>Do Something</button>;
}
```

---

## Examples

### Example 1: Employee List

See: `resources/js/components/examples/employee-list-example.tsx`

**Key Features:**
- ✅ No `useState` for data, loading, or error
- ✅ No `useEffect` for fetching
- ✅ Automatic caching and refetching
- ✅ Optimistic updates on delete
- ✅ Permission-based rendering
- ✅ Toast notifications

### Example 2: Create Employee Form

See: `resources/js/components/examples/create-employee-example.tsx`

**Key Features:**
- ✅ Zustand for form state (instead of multiple `useState`)
- ✅ React Query mutation for creating
- ✅ Automatic cache invalidation after creation
- ✅ Loading states handled automatically
- ✅ Error handling with notifications

---

## Best Practices

### 1. Select Only What You Need

**❌ Bad:**
```tsx
const store = useUIStore(); // Re-renders on ANY store change
```

**✅ Good:**
```tsx
const sidebarOpen = useUIStore((state) => state.sidebarOpen); // Only re-renders when sidebarOpen changes
```

### 2. Use Query Keys Consistently

```tsx
// Query keys are defined in hook files
export const employeeKeys = {
    all: ['employees'] as const,
    lists: () => [...employeeKeys.all, 'list'] as const,
    list: (filters) => [...employeeKeys.lists(), filters] as const,
};
```

### 3. Handle Loading and Error States

```tsx
const { data, isLoading, error } = useEmployees();

if (isLoading) return <Spinner />;
if (error) return <Error message={error.message} />;
return <EmployeeList employees={data.data} />;
```

### 4. Use Mutations with Optimistic Updates

```tsx
const deleteMutation = useDeleteEmployee();

const handleDelete = async (id: string) => {
    try {
        await deleteMutation.mutateAsync(id);
        notification.success('Deleted successfully');
    } catch (error) {
        notification.error('Failed to delete');
    }
};
```

### 5. Invalidate Queries After Mutations

This is already handled in the mutation hooks:

```tsx
onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
},
```

### 6. Use DevTools

React Query DevTools are enabled in development mode. Press the floating button to open the debugger and inspect:
- Active queries
- Query states
- Cache contents
- Background refetches

---

## Integration with Inertia.js

Since this app uses Inertia.js for routing and SSR, we combine both approaches:

- **Inertia Props**: For initial page data (SSR)
- **React Query**: For client-side data fetching, real-time updates, autocomplete, etc.
- **Zustand**: For UI state and global client-side state

**Example:**

```tsx
export default function Dashboard({ stats }: DashboardProps) {
    // Initial data from Inertia
    const { auth } = usePage().props;

    // Client-side data fetching for dynamic content
    const { data: employees } = useEmployees();

    // Global state
    const notification = useNotification();

    return <div>{/* ... */}</div>;
}
```

---

## Migration Checklist

When refactoring a component:

- [ ] Replace `useState` for server data with React Query hooks
- [ ] Replace `useEffect` for data fetching with React Query hooks
- [ ] Replace `useState` for UI state with Zustand stores
- [ ] Replace manual error/loading states with React Query states
- [ ] Replace manual cache management with React Query invalidation
- [ ] Replace alert() or console.log() with notification hooks
- [ ] Add proper TypeScript types
- [ ] Test loading, error, and success states

---

## Additional Resources

- [Zustand Documentation](https://docs.pmnd.rs/zustand/getting-started/introduction)
- [TanStack Query Documentation](https://tanstack.com/query/latest/docs/framework/react/overview)
- [Inertia.js Documentation](https://inertiajs.com/)
