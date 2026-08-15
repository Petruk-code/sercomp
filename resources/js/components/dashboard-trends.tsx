import { useState } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatCurrency } from '@/lib/utils';

type TrendPoint = { date: string; value: number };

type TrendData = {
    sales: TrendPoint[];
    services: TrendPoint[];
};

export type DashboardTrends = {
    '7d': TrendData;
    '30d': TrendData;
    '12m': TrendData;
};

type Period = keyof DashboardTrends;

const PERIOD_OPTIONS: { value: Period; label: string }[] = [
    { value: '7d', label: '7 Hari' },
    { value: '30d', label: '30 Hari' },
    { value: '12m', label: '12 Bulan' },
];

function formatLabel(date: string, monthly: boolean): string {
    const [year, month, day] = date.split('-').map(Number);

    if (monthly) {
        return new Date(year, month - 1, 1).toLocaleDateString('id-ID', {
            month: 'short',
            year: '2-digit',
        });
    }

    return new Date(year, month - 1, day).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });
}

function formatCompact(value: number): string {
    if (Math.abs(value) >= 1_000_000) {
        return `${(value / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })}jt`;
    }

    if (Math.abs(value) >= 1_000) {
        return `${(value / 1_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })}rb`;
    }

    return String(value);
}

const tickStyle = {
    fill: 'var(--muted-foreground)',
    fontSize: 11,
} as const;

const axisLineStyle = { stroke: 'var(--border)' } as const;

const tooltipContentStyle = {
    backgroundColor: 'var(--popover)',
    color: 'var(--popover-foreground)',
    border: '1px solid var(--border)',
    borderRadius: '0.5rem',
    fontSize: 12,
} as const;

const tooltipLabelStyle = { color: 'var(--popover-foreground)' } as const;

const tooltipItemStyle = { color: 'var(--popover-foreground)' } as const;

export function DashboardTrends({ trends }: { trends: DashboardTrends }) {
    const [period, setPeriod] = useState<Period>('7d');
    const data = trends[period];
    const monthly = period === '12m';

    const label = (date: string) => formatLabel(date, monthly);

    return (
        <Card>
            <CardHeader className="flex-row items-center justify-between">
                <CardTitle>Tren Penjualan & Servis</CardTitle>
                <ToggleGroup
                    type="single"
                    value={period}
                    onValueChange={(value) => {
                        if (value) {
                            setPeriod(value as Period);
                        }
                    }}
                    variant="outline"
                    size="sm"
                >
                    {PERIOD_OPTIONS.map((option) => (
                        <ToggleGroupItem
                            key={option.value}
                            value={option.value}
                        >
                            {option.label}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>
            </CardHeader>
            <CardContent className="grid gap-6 lg:grid-cols-2">
                <div>
                    <p className="mb-2 text-sm font-medium text-muted-foreground">
                        Penjualan
                    </p>
                    <ResponsiveContainer width="100%" height={220}>
                        <AreaChart
                            data={data.sales}
                            margin={{
                                top: 5,
                                right: 5,
                                left: 0,
                                bottom: 0,
                            }}
                        >
                            <defs>
                                <linearGradient
                                    id="salesFill"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor="var(--primary)"
                                        stopOpacity={0.3}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor="var(--primary)"
                                        stopOpacity={0}
                                    />
                                </linearGradient>
                            </defs>
                            <CartesianGrid
                                strokeDasharray="3 3"
                                stroke="var(--border)"
                            />
                            <XAxis
                                dataKey="date"
                                tickFormatter={label}
                                tick={tickStyle}
                                stroke="var(--border)"
                                axisLine={axisLineStyle}
                                tickLine={axisLineStyle}
                            />
                            <YAxis
                                tickFormatter={formatCompact}
                                tick={tickStyle}
                                stroke="var(--border)"
                                axisLine={axisLineStyle}
                                tickLine={axisLineStyle}
                                width={42}
                            />
                            <Tooltip
                                labelFormatter={(value) => label(String(value))}
                                formatter={(value) =>
                                    formatCurrency(Number(value))
                                }
                                contentStyle={tooltipContentStyle}
                                labelStyle={tooltipLabelStyle}
                                itemStyle={tooltipItemStyle}
                                cursor={{
                                    stroke: 'var(--border)',
                                }}
                            />
                            <Area
                                type="monotone"
                                dataKey="value"
                                name="Penjualan"
                                stroke="var(--primary)"
                                fill="url(#salesFill)"
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
                <div>
                    <p className="mb-2 text-sm font-medium text-muted-foreground">
                        Servis Masuk
                    </p>
                    <ResponsiveContainer width="100%" height={220}>
                        <BarChart
                            data={data.services}
                            margin={{
                                top: 5,
                                right: 5,
                                left: 0,
                                bottom: 0,
                            }}
                        >
                            <CartesianGrid
                                strokeDasharray="3 3"
                                stroke="var(--border)"
                            />
                            <XAxis
                                dataKey="date"
                                tickFormatter={label}
                                tick={tickStyle}
                                stroke="var(--border)"
                                axisLine={axisLineStyle}
                                tickLine={axisLineStyle}
                            />
                            <YAxis
                                allowDecimals={false}
                                tick={tickStyle}
                                stroke="var(--border)"
                                axisLine={axisLineStyle}
                                tickLine={axisLineStyle}
                                width={30}
                            />
                            <Tooltip
                                labelFormatter={(value) => label(String(value))}
                                formatter={(value) => `${value} servis`}
                                contentStyle={tooltipContentStyle}
                                labelStyle={tooltipLabelStyle}
                                itemStyle={tooltipItemStyle}
                                cursor={{ fill: 'var(--muted)', opacity: 0.6 }}
                            />
                            <Bar
                                dataKey="value"
                                name="Servis"
                                fill="var(--primary)"
                                radius={[4, 4, 0, 0]}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}
