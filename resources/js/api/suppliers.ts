import { apiRequest } from './client';
import type { PaginatedResponse, Supplier } from '../types/purchases';
import type { NewSupplierInput, SupplierFilters } from '../types/suppliers';

/**
 * Suppliers rarely number in the hundreds for this application, so a large
 * fixed page size stands in for an unpaginated list where one is needed
 * (e.g. populating a select). See the SupplierController pagination cap.
 */
const DROPDOWN_PAGE_SIZE = 100;

export function fetchSuppliers(filters: SupplierFilters): Promise<PaginatedResponse<Supplier>> {
    const params = new URLSearchParams();
    params.set('page', String(filters.page));
    params.set('per_page', String(filters.perPage));

    if (filters.search.trim()) {
        params.set('search', filters.search.trim());
    }

    return apiRequest<PaginatedResponse<Supplier>>(`/api/suppliers?${params.toString()}`);
}

export function fetchAllSuppliers(): Promise<{ data: Supplier[] }> {
    return apiRequest<{ data: Supplier[] }>(`/api/suppliers?per_page=${DROPDOWN_PAGE_SIZE}`);
}

export function createSupplier(input: NewSupplierInput): Promise<{ data: Supplier }> {
    return apiRequest<{ data: Supplier }>('/api/suppliers', {
        method: 'POST',
        body: JSON.stringify({
            name: input.name,
            email: input.email || null,
            phone: input.phone || null,
        }),
    });
}

export function updateSupplier(id: number, input: NewSupplierInput): Promise<{ data: Supplier }> {
    return apiRequest<{ data: Supplier }>(`/api/suppliers/${id}`, {
        method: 'PUT',
        body: JSON.stringify({
            name: input.name,
            email: input.email || null,
            phone: input.phone || null,
        }),
    });
}

export function deleteSupplier(id: number): Promise<void> {
    return apiRequest<void>(`/api/suppliers/${id}`, { method: 'DELETE' });
}
