import type { ReportSummary } from './reports';

export type TrendDay = {
    date: string;
    count: number;
    total: number;
};

export type ActivityTrend = {
    days: number;
    from: string;
    to: string;
    purchases: TrendDay[];
    sales_orders: TrendDay[];
};

export type DashboardOverview = {
    summary: ReportSummary;
    trend: ActivityTrend;
};
