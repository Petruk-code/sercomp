import { Link } from '@inertiajs/react';
import { Bell, BellRing, CheckCheck, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotifications } from '@/hooks/use-notifications';
import { formatDate } from '@/lib/utils';
import { index as notificationsIndex } from '@/routes/notifications';
import type { AppNotification } from '@/types';

export function NotificationsPopover() {
    const { summary, unreadCount, markRead, markAllRead, remove } =
        useNotifications();

    const recent = summary?.recent ?? [];

    const openNotification = (notification: AppNotification) => {
        if (notification.read_at === null) {
            markRead(notification.id);
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="group relative h-9 w-9 cursor-pointer"
                    aria-label="Notifikasi"
                >
                    {unreadCount > 0 ? (
                        <BellRing className="size-5 opacity-80 group-hover:opacity-100" />
                    ) : (
                        <Bell className="size-5 opacity-80 group-hover:opacity-100" />
                    )}
                    {unreadCount > 0 && (
                        <span className="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-80" align="end">
                <div className="flex items-center justify-between px-2 py-1.5">
                    <DropdownMenuLabel className="text-sm font-semibold">
                        Notifikasi
                    </DropdownMenuLabel>
                    {unreadCount > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={markAllRead}
                            className="h-7 gap-1.5 px-2 text-xs"
                        >
                            <CheckCheck className="h-3.5 w-3.5" />
                            Tandai semua dibaca
                        </Button>
                    )}
                </div>
                <DropdownMenuSeparator />
                {recent.length === 0 ? (
                    <div className="px-4 py-8 text-center text-sm text-muted-foreground">
                        Belum ada notifikasi.
                    </div>
                ) : (
                    <div className="max-h-80 overflow-y-auto p-1">
                        {recent.map((notification) => (
                            <div
                                key={notification.id}
                                className="group flex items-start gap-1 rounded-md hover:bg-accent"
                            >
                                <Link
                                    href={
                                        notification.action_url ??
                                        notificationsIndex.url()
                                    }
                                    onClick={() =>
                                        openNotification(notification)
                                    }
                                    className="flex min-w-0 flex-1 items-start gap-3 px-2 py-2.5"
                                >
                                    <span
                                        className={`mt-1.5 size-2 shrink-0 rounded-full ${
                                            notification.read_at === null
                                                ? 'bg-blue-500'
                                                : 'bg-neutral-300 dark:bg-neutral-600'
                                        }`}
                                    />
                                    <span className="min-w-0 flex-1">
                                        <span className="flex items-baseline justify-between gap-2">
                                            <span
                                                className={`truncate text-sm ${
                                                    notification.read_at ===
                                                    null
                                                        ? 'font-semibold'
                                                        : 'font-medium text-muted-foreground'
                                                }`}
                                            >
                                                {notification.title}
                                            </span>
                                            <span className="shrink-0 text-[11px] text-muted-foreground">
                                                {formatDate(
                                                    notification.created_at,
                                                )}
                                            </span>
                                        </span>
                                        <span className="mt-0.5 line-clamp-2 block text-xs text-muted-foreground">
                                            {notification.message}
                                        </span>
                                    </span>
                                </Link>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="mt-2.5 mr-1 h-6 w-6 shrink-0 opacity-0 group-hover:opacity-100"
                                    aria-label="Hapus notifikasi"
                                    onClick={() => remove(notification.id)}
                                >
                                    <Trash2 className="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        ))}
                    </div>
                )}
                <DropdownMenuSeparator />
                <Link
                    href={notificationsIndex.url()}
                    className="block rounded-md px-2 py-1.5 text-center text-sm font-medium text-accent-foreground hover:bg-accent"
                >
                    Lihat semua notifikasi
                </Link>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
