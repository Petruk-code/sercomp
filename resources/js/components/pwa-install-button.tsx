import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePwaInstall } from '@/hooks/use-pwa-install';

export function PwaInstallButton() {
    const { canInstall, install } = usePwaInstall();

    if (!canInstall) {
        return null;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9 cursor-pointer"
                    onClick={install}
                >
                    <Download className="!size-5 opacity-80 group-hover:opacity-100" />
                    <span className="sr-only">Instal aplikasi</span>
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                <p>Instal aplikasi</p>
            </TooltipContent>
        </Tooltip>
    );
}
