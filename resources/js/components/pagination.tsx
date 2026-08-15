import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { PaginatedData } from '@/types';

export function Pagination({ data }: { data: PaginatedData<unknown> }) {
    if (data.last_page <= 1) {
        return null;
    }

    const pages = Array.from(
        { length: data.last_page },
        (_, index) => index + 1,
    );

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 pt-4">
            <p className="text-sm text-muted-foreground">
                Menampilkan {data.from ?? 0}–{data.to ?? 0} dari {data.total}{' '}
                data
            </p>

            <div className="flex items-center gap-1">
                <Button
                    variant="outline"
                    size="sm"
                    asChild
                    disabled={!data.prev_page_url}
                >
                    {data.prev_page_url ? (
                        <Link href={data.prev_page_url} preserveScroll>
                            <ChevronLeft />
                            Sebelumnya
                        </Link>
                    ) : (
                        <span>
                            <ChevronLeft />
                            Sebelumnya
                        </span>
                    )}
                </Button>

                {pages.map((page) => (
                    <Button
                        key={page}
                        variant={
                            page === data.current_page ? 'default' : 'outline'
                        }
                        size="sm"
                        className="hidden min-w-9 sm:inline-flex"
                        asChild
                    >
                        <Link href={`${data.path}?page=${page}`} preserveScroll>
                            {page}
                        </Link>
                    </Button>
                ))}

                <Button
                    variant="outline"
                    size="sm"
                    asChild
                    disabled={!data.next_page_url}
                >
                    {data.next_page_url ? (
                        <Link href={data.next_page_url} preserveScroll>
                            Berikutnya
                            <ChevronRight />
                        </Link>
                    ) : (
                        <span>
                            Berikutnya
                            <ChevronRight />
                        </span>
                    )}
                </Button>
            </div>
        </div>
    );
}
