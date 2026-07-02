import { apiRequest } from './client';
import { fetchAllSuppliers } from './suppliers';
import type { NewPurchaseInput, PaginatedResponse, Purchase, PurchaseFilters, Supplier } from '../types/purchases';

function buildQuery(filters: PurchaseFilters): string {
    const params = new URLSearchParams();
    params.set('page', String(filters.page));
    params.set('per_page', String(filters.perPage));

    if (filters.status) {
        params.set('status', filters.status);
    }

    if (filters.supplierId) {
        params.set('supplier_id', String(filters.supplierId));
    }

    if (filters.search.trim()) {
        params.set('search', filters.search.trim());
    }

    return params.toString();
}

export function fetchPurchases(filters: PurchaseFilters): Promise<PaginatedResponse<Purchase>> {
    return apiRequest<PaginatedResponse<Purchase>>(`/api/purchases?${buildQuery(filters)}`);
}

export function createPurchase(input: NewPurchaseInput): Promise<{ data: Purchase }> {
    return apiRequest<{ data: Purchase }>('/api/purchases', {
        method: 'POST',
        body: JSON.stringify({
            supplier_id: input.supplier_id,
            order_date: input.order_date,
            expected_date: input.expected_date || null,
            notes: input.notes || null,
            items: input.items.map((item) => ({
                description: item.description,
                quantity: item.quantity,
                unit_price: item.unit_price,
            })),
        }),
    });
}

export function fetchSuppliers(): Promise<{ data: Supplier[] }> {
    return fetchAllSuppliers();
}
