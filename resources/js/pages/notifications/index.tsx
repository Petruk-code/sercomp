import { Head, Link, router } from '@inertiajs/react';
import { CheckCheck, ExternalLink, Trash2 } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useNotifications } from '@/hooks/use-notifications';
import { formatDate } from '@/lib/utils';
import { index as notificationsIndex } from '@/routes/notifications';
import type { AppNotification, PaginatedData } from '@/types';

type Props = {
    notifications: PaginatedData<AppNotification>;
};

export default function NotificationsIndex({ notifications }: Props) {
    const { unreadCount, markRead, markAllRead, remove } = useNotifications();

    const refreshList = () => {
        router.reload({
            only: ['notifications'],
        });
    };

    const handleMarkAllRead = () => {
        if (unreadCount === 0) {
            return;
        }

        markAllRead();
        refreshList();
    };

    const handleMarkRead = (notification: AppNotification) => {
        if (notification.read_at !== null) {
            return;
        }

        markRead(notification.id);
        refreshList();
    };

    const handleRemove = (notification: AppNotification) => {
        remove(notification.id);
        refreshList();
    };

    return (
        <>
            <Head title="Notifikasi" />

            <PageHeader
                title="Notifikasi"
                description="Riwayat notifikasi sistem dan peringatan."
            >
                {unreadCount > 0 && (
                    <Button variant="outline" onClick={handleMarkAllRead}>
                        <CheckCheck />
                        Tandai semua dibaca
                    </Button>
                )}
            </PageHeader>

            {notifications.data.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center">
                    <p className="text-sm text-muted-foreground">
                        Belum ada notifikasi.
                    </p>
                </div>
            ) : (
                <div className="space-y-2">
                    {notifications.data.map((notification) => {
                        const unread = notification.read_at === null;

                        return (
                            <div
                                key={notification.id}
                                className="flex items-start gap-3 rounded-lg border bg-card p-4 shadow-sm"
                            >
                                <span
                                    className={`mt-1.5 size-2.5 shrink-0 rounded-full ${
                                        unread
                                            ? 'bg-blue-500'
                                            : 'bg-neutral-300 dark:bg-neutral-600'
                                    }`}
                                />
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                        <h3
                                            className={`text-sm ${
                                                unread
                                                    ? 'font-semibold'
                                                    : 'font-medium text-muted-foreground'
                                            }`}
                                        >
                                            {notification.title}
                                        </h3>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDate(
                                                notification.created_at,
                                            )}
                                        </span>
                                    </div>
                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                        {notification.message}
                                    </p>
                                    <div className="mt-2 flex items-center gap-2">
                                        {unread && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="h-7 gap-1 px-2 text-xs"
                                                onClick={() =>
                                                    handleMarkRead(notification)
                                                }
                                            >
                                                <CheckCheck className="h-3.5 w-3.5" />
                                                Tandai dibaca
                                            </Button>
                                        )}
                                        {notification.action_url && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-7 gap-1 px-2 text-xs"
                                                asChild
                                            >
                                                <Link
                                                    href={
                                                        notification.action_url
                                                    }
                                                >
                                                    <ExternalLink className="h-3.5 w-3.5" />
                                                    Buka
                                                </Link>
                                            </Button>
                                        )}
                                        {!unread &&
                                            !notification.action_url && (
                                                <Badge
                                                    variant="secondary"
                                                    className="text-xs"
                                                >
                                                    Dibaca
                                                </Badge>
                                            )}
                                    </div>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-7 w-7 shrink-0"
                                    aria-label="Hapus notifikasi"
                                    onClick={() => handleRemove(notification)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        );
                    })}
                </div>
            )}

            <Pagination data={notifications} />
        </>
    );
}

NotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Notifikasi', href: notificationsIndex.url() }],
};
