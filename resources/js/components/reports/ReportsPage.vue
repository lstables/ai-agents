<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { fetchReportSummary } from '../../api/reports';
import type { ReportFilters, ReportSummary } from '../../types/reports';

const filters = reactive<ReportFilters>({ from: '', to: '' });
const summary = ref<ReportSummary | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');

async function load() {
    loadState.value = 'loading';
    errorMessage.value = '';

    try {
        summary.value = await fetchReportSummary(filters);
        loadState.value = 'ready';
    } catch (error) {
        loadState.value = 'error';
        errorMessage.value = error instanceof Error ? error.message : 'Could not load reports.';
    }
}

onMounted(load);

function clearFilters() {
    filters.from = '';
    filters.to = '';
    load();
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-cyan-700">Reports</p>
                <h2 class="mt-1 text-2xl font-bold text-zinc-950">Operational reports</h2>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="block text-xs font-semibold text-zinc-600" for="report_from">From</label>
                    <input
                        id="report_from"
                        v-model="filters.from"
                        type="date"
                        class="mt-1 rounded-md border border-zinc-300 px-3 py-2 text-sm"
                        @change="load"
                    >
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-600" for="report_to">To</label>
                    <input
                        id="report_to"
                        v-model="filters.to"
                        type="date"
                        class="mt-1 rounded-md border border-zinc-300 px-3 py-2 text-sm"
                        @change="load"
                    >
                </div>
                <button
                    type="button"
                    class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>
        </div>

        <div v-if="loadState === 'loading'" class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500">
            Loading reports…
        </div>

        <div v-else-if="loadState === 'error'" class="rounded-lg border border-rose-200 bg-rose-50 p-8 text-center text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <template v-else-if="summary">
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-zinc-950">Purchasing</h3>
                    <div class="mt-3 flex items-end gap-6">
                        <div>
                            <p class="text-xs font-semibold uppercase text-zinc-500">Orders</p>
                            <p class="text-2xl font-bold text-zinc-950">{{ summary.purchasing.total_orders }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-zinc-500">Total spend</p>
                            <p class="text-2xl font-bold text-zinc-950">{{ summary.purchasing.total_spend.toFixed(2) }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                        <div v-for="(count, status) in summary.purchasing.by_status" :key="status" class="rounded-md bg-zinc-50 px-3 py-2">
                            <dt class="text-xs capitalize text-zinc-500">{{ status }}</dt>
                            <dd class="font-semibold text-zinc-900">{{ count }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-zinc-950">Sales</h3>
                    <div class="mt-3 flex items-end gap-6">
                        <div>
                            <p class="text-xs font-semibold uppercase text-zinc-500">Orders</p>
                            <p class="text-2xl font-bold text-zinc-950">{{ summary.sales.total_orders }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-zinc-500">Total revenue</p>
                            <p class="text-2xl font-bold text-zinc-950">{{ summary.sales.total_revenue.toFixed(2) }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                        <div v-for="(count, status) in summary.sales.by_status" :key="status" class="rounded-md bg-zinc-50 px-3 py-2">
                            <dt class="text-xs capitalize text-zinc-500">{{ status }}</dt>
                            <dd class="font-semibold text-zinc-900">{{ count }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h3 class="text-lg font-bold text-zinc-950">
                        Low stock ({{ summary.inventory.below_reorder_level_count }})
                    </h3>
                </div>
                <div v-if="summary.inventory.items.length === 0" class="px-5 py-6 text-center text-sm text-zinc-500">
                    Nothing is at or below its reorder level.
                </div>
                <table v-else class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">SKU</th>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3 text-right">Quantity</th>
                            <th class="px-5 py-3 text-right">Reorder level</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <tr v-for="item in summary.inventory.items" :key="item.id">
                            <td class="px-5 py-3 font-semibold text-zinc-900">{{ item.sku }}</td>
                            <td class="px-5 py-3 text-zinc-700">{{ item.name }}</td>
                            <td class="px-5 py-3 text-right text-zinc-700">{{ item.quantity_on_hand }}</td>
                            <td class="px-5 py-3 text-right text-zinc-700">{{ item.reorder_level }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
                    <div class="border-b border-zinc-200 px-5 py-4">
                        <h3 class="text-lg font-bold text-zinc-950">Top suppliers by spend</h3>
                    </div>
                    <div v-if="summary.top_suppliers.length === 0" class="px-5 py-6 text-center text-sm text-zinc-500">
                        No purchase spend yet.
                    </div>
                    <table v-else class="w-full text-left text-sm">
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="row in summary.top_suppliers" :key="row.supplier.id">
                                <td class="px-5 py-3 text-zinc-900">{{ row.supplier.name }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-zinc-900">{{ row.total_spend.toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
                    <div class="border-b border-zinc-200 px-5 py-4">
                        <h3 class="text-lg font-bold text-zinc-950">Top customers by revenue</h3>
                    </div>
                    <div v-if="summary.top_customers.length === 0" class="px-5 py-6 text-center text-sm text-zinc-500">
                        No sales revenue yet.
                    </div>
                    <table v-else class="w-full text-left text-sm">
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="row in summary.top_customers" :key="row.customer.id">
                                <td class="px-5 py-3 text-zinc-900">{{ row.customer.name }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-zinc-900">{{ row.total_revenue.toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>
