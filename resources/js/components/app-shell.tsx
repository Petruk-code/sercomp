import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { NotificationAlerts } from '@/components/notification-alerts';
import { SidebarProvider } from '@/components/ui/sidebar';
import { NotificationsProvider } from '@/hooks/use-notifications';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = usePage().props.sidebarOpen;

    const content =
        variant === 'header' ? (
            <div className="flex min-h-screen w-full flex-col">
                {children}
                <NotificationAlerts />
            </div>
        ) : (
            <SidebarProvider defaultOpen={isOpen}>
                {children}
                <NotificationAlerts />
            </SidebarProvider>
        );

    return <NotificationsProvider>{content}</NotificationsProvider>;
}
