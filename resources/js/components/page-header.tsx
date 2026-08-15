import type { ReactNode } from 'react';
import Heading from '@/components/heading';

export function PageHeader({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading title={title} description={description} />
            {children ? (
                <div className="flex items-center gap-2">{children}</div>
            ) : null}
        </div>
    );
}
