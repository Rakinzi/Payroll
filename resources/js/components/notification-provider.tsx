import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useUIStore } from '@/stores/ui-store';
import { AlertCircle, CheckCircle, Info, XCircle } from 'lucide-react';
import { useEffect } from 'react';

const iconMap = {
    success: CheckCircle,
    error: XCircle,
    warning: AlertCircle,
    info: Info,
};

const variantMap = {
    success: 'default',
    error: 'destructive',
    warning: 'default',
    info: 'default',
} as const;

export function NotificationProvider() {
    const notifications = useUIStore((state) => state.notifications);
    const removeNotification = useUIStore((state) => state.removeNotification);

    useEffect(() => {
        if (notifications.length === 0) return;

        const timers = notifications.map((notification) => {
            if (notification.duration && notification.duration > 0) {
                return setTimeout(() => {
                    removeNotification(notification.id);
                }, notification.duration);
            }
            return null;
        });

        return () => {
            timers.forEach((timer) => {
                if (timer) clearTimeout(timer);
            });
        };
    }, [notifications, removeNotification]);

    if (notifications.length === 0) return null;

    return (
        <div className="pointer-events-none fixed inset-0 z-50 flex flex-col items-end justify-end gap-2 p-4">
            {notifications.map((notification) => {
                const Icon = iconMap[notification.type];
                return (
                    <div
                        key={notification.id}
                        className="pointer-events-auto animate-in slide-in-from-right-full"
                    >
                        <Alert variant={variantMap[notification.type]} className="w-96">
                            <Icon className="h-4 w-4" />
                            {notification.title && (
                                <AlertTitle>{notification.title}</AlertTitle>
                            )}
                            <AlertDescription>{notification.message}</AlertDescription>
                            <button
                                onClick={() => removeNotification(notification.id)}
                                className="absolute right-2 top-2 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none"
                            >
                                <XCircle className="h-4 w-4" />
                                <span className="sr-only">Close</span>
                            </button>
                        </Alert>
                    </div>
                );
            })}
        </div>
    );
}
