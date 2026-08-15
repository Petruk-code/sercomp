import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { useNotifications } from '@/hooks/use-notifications';
import { index as notificationsIndex } from '@/routes/notifications';
import type { AppNotification } from '@/types';

export function NotificationAlerts() {
    const { summary, markRead } = useNotifications();

    const initialized = useRef(false);
    const seenIds = useRef<Set<string>>(new Set());

    useEffect(() => {
        if (!summary) {
            return;
        }

        const recent = summary.recent;

        if (!initialized.current) {
            initialized.current = true;
            seenIds.current = new Set(
                recent.map((notification) => notification.id),
            );

            return;
        }

        for (const notification of recent) {
            if (seenIds.current.has(notification.id)) {
                continue;
            }

            showToast(notification, () => markRead(notification.id));
        }

        seenIds.current = new Set(
            recent.map((notification) => notification.id),
        );
    }, [summary, markRead]);

    return null;
}

function showToast(notification: AppNotification, onOpen: () => void): void {
    toast.info(notification.title, {
        description: notification.message,
        action: {
            label: 'Lihat',
            onClick: () => {
                onOpen();
                router.visit(
                    notification.action_url ?? notificationsIndex.url(),
                );
            },
        },
    });
}
