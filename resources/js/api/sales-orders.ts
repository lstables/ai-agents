import { apiRequest } from './client';
import type { PaginatedResponse } from '../types/purchases';
import type { NewSalesOrderInput, SalesOrder, SalesOrderFilters } from '../types/sales-orders';

function buildQuery(filters: SalesOrderFilters): string {
    const params = new URLSearchParams();
    params.set('page', String(filters.page));
    params.set('per_page', String(filters.perPage));

    if (filters.status) {
        params.set('status', filters.status);
    }

    if (filters.customerId) {
        params.set('customer_id', String(filters.customerId));
    }

    if (filters.search.trim()) {
        params.set('search', filters.search.trim());
    }

    return params.toString();
}

export function fetchSalesOrders(filters: SalesOrderFilters): Promise<PaginatedResponse<SalesOrder>> {
    return apiRequest<PaginatedResponse<SalesOrder>>(`/api/sales-orders?${buildQuery(filters)}`);
}

export function createSalesOrder(input: NewSalesOrderInput): Promise<{ data: SalesOrder }> {
    return apiRequest<{ data: SalesOrder }>('/api/sales-orders', {
        method: 'POST',
        body: JSON.stringify({
            customer_id: input.customer_id,
            order_date: input.order_date,
            expected_date: input.expected_date || null,
            notes: input.notes || null,
            items: input.items.map((item) => ({
                inventory_item_id: item.inventory_item_id || null,
                description: item.description,
                quantity: item.quantity,
                unit_price: item.unit_price,
            })),
        }),
    });
}
