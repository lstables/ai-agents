import { apiRequest } from './client';
import type { DashboardOverview } from '../types/dashboard';

export function fetchDashboardOverview(): Promise<DashboardOverview> {
    return apiRequest<DashboardOverview>('/api/dashboard/overview');
}
