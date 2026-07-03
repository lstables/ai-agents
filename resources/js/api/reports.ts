import { apiRequest } from './client';
import type { ReportFilters, ReportSummary } from '../types/reports';

export function fetchReportSummary(filters: ReportFilters): Promise<ReportSummary> {
    const params = new URLSearchParams();

    if (filters.from.trim()) {
        params.set('from', filters.from.trim());
    }

    if (filters.to.trim()) {
        params.set('to', filters.to.trim());
    }

    const query = params.toString();

    return apiRequest<ReportSummary>(`/api/reports/summary${query ? `?${query}` : ''}`);
}
