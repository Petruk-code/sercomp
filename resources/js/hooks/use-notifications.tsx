import { useHttp } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import {
    destroy as destroyNotification,
    read as readNotification,
    readAll as readAllNotifications,
    summary as summaryNotifications,
} from '@/routes/notifications';
import type { AppNotification } from '@/types';

const POLL_INTERVAL_MS = 30_000;

export type NotificationSummary = {
    unread_count: number;
    recent: AppNotification[];
};

type NotificationsContextValue = {
    summary: NotificationSummary | null;
    unreadCount: number;
    markRead: (id: string) => void;
    markAllRead: () => void;
    remove: (id: string) => void;
    refresh: () => void;
};

const NotificationsContext = createContext<NotificationsContextValue | null>(
    null,
);

export function NotificationsProvider({ children }: { children: ReactNode }) {
    const [summary, setSummary] = useState<NotificationSummary | null>(null);
    const {
        get,
        post,
        delete: destroy,
    } = useHttp<Record<string, never>, NotificationSummary>({});

    const refresh = useCallback(() => {
        get(summaryNotifications.url())
            .then((data) => setSummary(data))
            .catch(() => {
                // polling stays silent on transient network errors
            });
    }, [get]);

    useEffect(() => {
        refresh();

        const interval = window.setInterval(refresh, POLL_INTERVAL_MS);
        const onFocus = () => refresh();

        window.addEventListener('focus', onFocus);

        return () => {
            window.clearInterval(interval);
            window.removeEventListener('focus', onFocus);
        };
    }, [refresh]);

    const markRead = useCallback(
        (id: string) => {
            setSummary((current) => applyOptimisticRead(current, id));

            post(readNotification.url({ notification: id }))
                .then((data) => setSummary(data))
                .catch(() => refresh());
        },
        [post, refresh],
    );

    const markAllRead = useCallback(() => {
        setSummary((current) =>
            current
                ? {
                      unread_count: 0,
                      recent: current.recent.map((notification) =>
                          notification.read_at === null
                              ? {
                                    ...notification,
                                    read_at: new Date().toISOString(),
                                }
                              : notification,
                      ),
                  }
                : current,
        );

        post(readAllNotifications.url())
            .then((data) => setSummary(data))
            .catch(() => refresh());
    }, [post, refresh]);

    const remove = useCallback(
        (id: string) => {
            setSummary((current) => {
                if (!current) {
                    return current;
                }

                const wasUnread = current.recent.some(
                    (notification) =>
                        notification.id === id && notification.read_at === null,
                );

                return {
                    unread_count: wasUnread
                        ? Math.max(current.unread_count - 1, 0)
                        : current.unread_count,
                    recent: current.recent.filter(
                        (notification) => notification.id !== id,
                    ),
                };
            });

            destroy(destroyNotification.url({ notification: id }))
                .then((data) => setSummary(data))
                .catch(() => refresh());
        },
        [destroy, refresh],
    );

    return (
        <NotificationsContext.Provider
            value={{
                summary,
                unreadCount: summary?.unread_count ?? 0,
                markRead,
                markAllRead,
                remove,
                refresh,
            }}
        >
            {children}
        </NotificationsContext.Provider>
    );
}

function applyOptimisticRead(
    current: NotificationSummary | null,
    id: string,
): NotificationSummary | null {
    if (!current) {
        return current;
    }

    const target = current.recent.find(
        (notification) => notification.id === id,
    );

    if (!target || target.read_at !== null) {
        return current;
    }

    return {
        unread_count: Math.max(current.unread_count - 1, 0),
        recent: current.recent.map((notification) =>
            notification.id === id
                ? {
                      ...notification,
                      read_at: new Date().toISOString(),
                  }
                : notification,
        ),
    };
}

export function useNotifications(): NotificationsContextValue {
    const context = useContext(NotificationsContext);

    if (!context) {
        throw new Error(
            'useNotifications must be used within a NotificationsProvider',
        );
    }

    return context;
}
