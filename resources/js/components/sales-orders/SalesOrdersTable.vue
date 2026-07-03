<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { fetchSalesOrders } from '../../api/sales-orders';
import SalesOrderStatusBadge from './SalesOrderStatusBadge.vue';
import { SALES_ORDER_STATUSES } from '../../types/sales-orders';
import type { PaginationMeta } from '../../types/purchases';
import type { Customer } from '../../types/customers';
import type { SalesOrder, SalesOrderFilters } from '../../types/sales-orders';

const props = defineProps<{ customers: Customer[]; refreshToken: number }>();

const salesOrders = ref<SalesOrder[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');

const filters = reactive<SalesOrderFilters>({
    status: '',
    customerId: '',
    search: '',
    page: 1,
    perPage: 10,
});

let searchDebounce: ReturnType<typeof setTimeout> | undefined;

async function load() {
    loadState.value = 'loading';
    errorMessage.value = '';

    try {
        const response = await fetchSalesOrders(filters);
        salesOrders.value = response.data;
        meta.value = response.meta;
        loadState.value = 'ready';
    } catch (error) {
        loadState.value = 'error';
        errorMessage.value = error instanceof Error ? error.message : 'Could not load sales orders.';
    }
}

onMounted(load);

watch(() => props.refreshToken, load);
watch(() => [filters.status, filters.customerId], () => {
    filters.page = 1;
    load();
});
watch(() => filters.search, () => {
    filters.page = 1;
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(load, 300);
});

function goToPage(page: number) {
    if (!meta.value || page < 1 || page > meta.value.last_page) {
        return;
    }

    filters.page = page;
    load();
}
</script>

<template>
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-bold text-zinc-950">Sales Orders</h3>

            <div class="flex flex-wrap gap-2">
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search reference or customer"
                    class="rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <select v-model="filters.status" class="rounded-md border border-zinc-300 px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    <option v-for="status in SALES_ORDER_STATUSES" :key="status" :value="status">
                        {{ status }}
                    </option>
                </select>
                <select v-model="filters.customerId" class="rounded-md border border-zinc-300 px-3 py-2 text-sm">
                    <option value="">All customers</option>
                    <option v-for="customer in props.customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="loadState === 'loading'" class="px-5 py-8 text-center text-sm text-zinc-500">
            Loading sales orders…
        </div>

        <div v-else-if="loadState === 'error'" class="px-5 py-8 text-center text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <div v-else-if="salesOrders.length === 0" class="px-5 py-8 text-center text-sm text-zinc-500">
            No sales orders match these filters.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                    <tr>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Order date</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <tr v-for="salesOrder in salesOrders" :key="salesOrder.id">
                        <td class="px-5 py-3 font-semibold text-zinc-900">{{ salesOrder.reference }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ salesOrder.customer?.name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <SalesOrderStatusBadge :status="salesOrder.status" />
                        </td>
                        <td class="px-5 py-3 text-zinc-700">{{ salesOrder.order_date }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-zinc-900">
                            {{ salesOrder.total_amount.toFixed(2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="meta && meta.last_page > 1"
            class="flex items-center justify-between border-t border-zinc-200 px-5 py-3 text-sm text-zinc-600"
        >
            <span>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)</span>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded-md border border-zinc-300 px-3 py-1 font-semibold hover:bg-zinc-50 disabled:opacity-40"
                    :disabled="meta.current_page <= 1"
                    @click="goToPage(meta.current_page - 1)"
                >
                    Previous
                </button>
                <button
                    type="button"
                    class="rounded-md border border-zinc-300 px-3 py-1 font-semibold hover:bg-zinc-50 disabled:opacity-40"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="goToPage(meta.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
