<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { fetchPurchases } from '../../api/purchases';
import PurchaseStatusBadge from './PurchaseStatusBadge.vue';
import { PURCHASE_STATUSES } from '../../types/purchases';
import type { PaginationMeta, Purchase, PurchaseFilters, Supplier } from '../../types/purchases';

const props = defineProps<{ suppliers: Supplier[]; refreshToken: number }>();

const purchases = ref<Purchase[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');

const filters = reactive<PurchaseFilters>({
    status: '',
    supplierId: '',
    search: '',
    page: 1,
    perPage: 10,
});

let searchDebounce: ReturnType<typeof setTimeout> | undefined;

async function load() {
    loadState.value = 'loading';
    errorMessage.value = '';

    try {
        const response = await fetchPurchases(filters);
        purchases.value = response.data;
        meta.value = response.meta;
        loadState.value = 'ready';
    } catch (error) {
        loadState.value = 'error';
        errorMessage.value = error instanceof Error ? error.message : 'Could not load purchases.';
    }
}

onMounted(load);

watch(() => props.refreshToken, load);
watch(() => [filters.status, filters.supplierId], () => {
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
            <h3 class="text-lg font-bold text-zinc-950">Purchases</h3>

            <div class="flex flex-wrap gap-2">
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search reference or supplier"
                    class="rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <select v-model="filters.status" class="rounded-md border border-zinc-300 px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    <option v-for="status in PURCHASE_STATUSES" :key="status" :value="status">
                        {{ status }}
                    </option>
                </select>
                <select v-model="filters.supplierId" class="rounded-md border border-zinc-300 px-3 py-2 text-sm">
                    <option value="">All suppliers</option>
                    <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">
                        {{ supplier.name }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="loadState === 'loading'" class="px-5 py-8 text-center text-sm text-zinc-500">
            Loading purchases…
        </div>

        <div v-else-if="loadState === 'error'" class="px-5 py-8 text-center text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <div v-else-if="purchases.length === 0" class="px-5 py-8 text-center text-sm text-zinc-500">
            No purchases match these filters.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                    <tr>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Order date</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <tr v-for="purchase in purchases" :key="purchase.id">
                        <td class="px-5 py-3 font-semibold text-zinc-900">{{ purchase.reference }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ purchase.supplier?.name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <PurchaseStatusBadge :status="purchase.status" />
                        </td>
                        <td class="px-5 py-3 text-zinc-700">{{ purchase.order_date }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-zinc-900">
                            {{ purchase.total_amount.toFixed(2) }}
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
