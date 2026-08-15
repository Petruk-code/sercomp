import { useEffect, useState } from 'react';

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
};

export type PwaInstallReturn = {
    canInstall: boolean;
    install: () => Promise<void>;
};

export function usePwaInstall(): PwaInstallReturn {
    const [canInstall, setCanInstall] = useState(false);
    const [promptEvent, setPromptEvent] =
        useState<BeforeInstallPromptEvent | null>(null);

    useEffect(() => {
        const onBeforeInstallPrompt = (event: Event) => {
            event.preventDefault();
            setPromptEvent(event as BeforeInstallPromptEvent);
            setCanInstall(true);
        };

        const onAppInstalled = () => {
            setCanInstall(false);
            setPromptEvent(null);
        };

        window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
        window.addEventListener('appinstalled', onAppInstalled);

        return () => {
            window.removeEventListener(
                'beforeinstallprompt',
                onBeforeInstallPrompt,
            );
            window.removeEventListener('appinstalled', onAppInstalled);
        };
    }, []);

    const install = async (): Promise<void> => {
        if (!promptEvent) {
            return;
        }

        await promptEvent.prompt();
        setCanInstall(false);
        setPromptEvent(null);
    };

    return { canInstall, install };
}
