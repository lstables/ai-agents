import { apiRequest } from './client';
import type { PaginatedResponse } from '../types/purchases';
import type { InventoryItem, InventoryItemFilters, NewInventoryItemInput } from '../types/inventory';

export function fetchInventoryItems(filters: InventoryItemFilters): Promise<PaginatedResponse<InventoryItem>> {
    const params = new URLSearchParams();
    params.set('page', String(filters.page));
    params.set('per_page', String(filters.perPage));

    if (filters.belowReorderLevel) {
        params.set('below_reorder_level', '1');
    }

    if (filters.search.trim()) {
        params.set('search', filters.search.trim());
    }

    return apiRequest<PaginatedResponse<InventoryItem>>(`/api/inventory-items?${params.toString()}`);
}

function payload(input: NewInventoryItemInput) {
    return {
        sku: input.sku,
        name: input.name,
        description: input.description || null,
        quantity_on_hand: input.quantity_on_hand,
        reorder_level: input.reorder_level || null,
        unit: input.unit || null,
    };
}

export function createInventoryItem(input: NewInventoryItemInput): Promise<{ data: InventoryItem }> {
    return apiRequest<{ data: InventoryItem }>('/api/inventory-items', {
        method: 'POST',
        body: JSON.stringify(payload(input)),
    });
}

export function updateInventoryItem(id: number, input: NewInventoryItemInput): Promise<{ data: InventoryItem }> {
    return apiRequest<{ data: InventoryItem }>(`/api/inventory-items/${id}`, {
        method: 'PUT',
        body: JSON.stringify(payload(input)),
    });
}

export function deleteInventoryItem(id: number): Promise<void> {
    return apiRequest<void>(`/api/inventory-items/${id}`, { method: 'DELETE' });
}
