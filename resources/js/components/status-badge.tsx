import { Badge } from '@/components/ui/badge';
import type { ServiceOrderStatus } from '@/types';

const serviceStatusVariants: Record<
    ServiceOrderStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    pending: 'secondary',
    diagnosed: 'outline',
    in_progress: 'default',
    done: 'secondary',
    taken: 'outline',
    cancelled: 'destructive',
};

const serviceStatusLabels: Record<ServiceOrderStatus, string> = {
    pending: 'Pending',
    diagnosed: 'Diagnosa',
    in_progress: 'Proses',
    done: 'Selesai',
    taken: 'Diambil',
    cancelled: 'Dibatalkan',
};

export function ServiceStatusBadge({ status }: { status: ServiceOrderStatus }) {
    return (
        <Badge variant={serviceStatusVariants[status]}>
            {serviceStatusLabels[status]}
        </Badge>
    );
}

export function PaymentStatusBadge({
    status,
    labels,
}: {
    status: 'paid' | 'partial' | 'unpaid';
    labels: Record<string, string>;
}) {
    const variant =
        status === 'paid'
            ? 'default'
            : status === 'partial'
              ? 'outline'
              : 'destructive';

    return <Badge variant={variant}>{labels[status] ?? status}</Badge>;
}

export function ReturnResultBadge({
    result,
    labels,
}: {
    result: string;
    labels: Record<string, string>;
}) {
    const variant =
        result === 'replacement'
            ? 'outline'
            : result === 'refund'
              ? 'default'
              : 'destructive';

    return <Badge variant={variant}>{labels[result] ?? result}</Badge>;
}

const warrantyStatusLabels: Record<string, string> = {
    none: 'Tanpa Garansi',
    pending: 'Garansi (menunggu)',
    active: 'Garansi Aktif',
    expired: 'Garansi Habis',
};

function warrantyStatusOf(
    warrantyDays: number | null,
    warrantyEndDate: string | null,
): 'none' | 'pending' | 'active' | 'expired' {
    if (!warrantyDays) {
        return 'none';
    }

    if (!warrantyEndDate) {
        return 'pending';
    }

    const end = new Date(`${warrantyEndDate}T23:59:59`);

    return end.getTime() < Date.now() ? 'expired' : 'active';
}

export function WarrantyBadge({
    warrantyDays,
    warrantyEndDate,
}: {
    warrantyDays: number | null;
    warrantyEndDate: string | null;
}) {
    const status = warrantyStatusOf(warrantyDays, warrantyEndDate);

    const variant =
        status === 'active'
            ? 'default'
            : status === 'expired'
              ? 'destructive'
              : 'outline';

    return <Badge variant={variant}>{warrantyStatusLabels[status]}</Badge>;
}
