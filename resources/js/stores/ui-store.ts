import { create } from 'zustand';
import { devtools, persist } from 'zustand/middleware';

interface Notification {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    title?: string;
    duration?: number;
}

interface Modal {
    id: string;
    component: string;
    props?: Record<string, any>;
}

interface UIState {
    // Sidebar
    sidebarOpen: boolean;
    toggleSidebar: () => void;
    setSidebarOpen: (open: boolean) => void;

    // Modals
    modals: Modal[];
    openModal: (modal: Omit<Modal, 'id'>) => void;
    closeModal: (id: string) => void;
    closeAllModals: () => void;

    // Notifications
    notifications: Notification[];
    addNotification: (notification: Omit<Notification, 'id'>) => void;
    removeNotification: (id: string) => void;
    clearNotifications: () => void;

    // Loading states
    globalLoading: boolean;
    setGlobalLoading: (loading: boolean) => void;
}

export const useUIStore = create<UIState>()(
    devtools(
        persist(
            (set) => ({
                // Sidebar
                sidebarOpen: true,
                toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
                setSidebarOpen: (open) => set({ sidebarOpen: open }),

                // Modals
                modals: [],
                openModal: (modal) =>
                    set((state) => ({
                        modals: [...state.modals, { ...modal, id: crypto.randomUUID() }],
                    })),
                closeModal: (id) =>
                    set((state) => ({
                        modals: state.modals.filter((modal) => modal.id !== id),
                    })),
                closeAllModals: () => set({ modals: [] }),

                // Notifications
                notifications: [],
                addNotification: (notification) =>
                    set((state) => ({
                        notifications: [
                            ...state.notifications,
                            {
                                ...notification,
                                id: crypto.randomUUID(),
                                duration: notification.duration || 5000,
                            },
                        ],
                    })),
                removeNotification: (id) =>
                    set((state) => ({
                        notifications: state.notifications.filter((notif) => notif.id !== id),
                    })),
                clearNotifications: () => set({ notifications: [] }),

                // Loading states
                globalLoading: false,
                setGlobalLoading: (loading) => set({ globalLoading: loading }),
            }),
            {
                name: 'ui-storage',
                partialize: (state) => ({ sidebarOpen: state.sidebarOpen }),
            },
        ),
        { name: 'UIStore' },
    ),
);
