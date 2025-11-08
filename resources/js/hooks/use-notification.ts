import { useUIStore } from '@/stores/ui-store';
import { useCallback } from 'react';

export function useNotification() {
    const addNotification = useUIStore((state) => state.addNotification);
    const removeNotification = useUIStore((state) => state.removeNotification);
    const clearNotifications = useUIStore((state) => state.clearNotifications);

    const success = useCallback(
        (message: string, title?: string, duration?: number) => {
            addNotification({ type: 'success', message, title, duration });
        },
        [addNotification],
    );

    const error = useCallback(
        (message: string, title?: string, duration?: number) => {
            addNotification({ type: 'error', message, title, duration });
        },
        [addNotification],
    );

    const warning = useCallback(
        (message: string, title?: string, duration?: number) => {
            addNotification({ type: 'warning', message, title, duration });
        },
        [addNotification],
    );

    const info = useCallback(
        (message: string, title?: string, duration?: number) => {
            addNotification({ type: 'info', message, title, duration });
        },
        [addNotification],
    );

    return {
        success,
        error,
        warning,
        info,
        remove: removeNotification,
        clear: clearNotifications,
    };
}
