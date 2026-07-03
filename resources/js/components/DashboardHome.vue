<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { fetchDashboardOverview } from '../api/dashboard';
import type { DashboardOverview } from '../types/dashboard';
import BarChart from './charts/BarChart.vue';
import TrendLineChart from './charts/TrendLineChart.vue';

const overview = ref<DashboardOverview | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');

async function load() {
    loadState.value = 'loading';
    errorMessage.value = '';

    try {
        overview.value = await fetchDashboardOverview();
        loadState.value = 'ready';
    } catch (error) {
        loadState.value = 'error';
        errorMessage.value = error instanceof Error ? error.message : 'Could not load the dashboard.';
    }
}

onMounted(load);

const statusClass = {
    healthy: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    attention: 'border-rose-200 bg-rose-50 text-rose-800',
};

const metrics = computed(() => {
    if (!overview.value) {
        return [];
    }

    const { purchasing, sales, inventory } = overview.value.summary;

    return [
        {
            label: 'Purchase orders',
            value: String(purchasing.total_orders),
            trend: `${purchasing.total_spend.toFixed(2)} total spend`,
            status: 'healthy' as const,
        },
        {
            label: 'Sales orders',
            value: String(sales.total_orders),
            trend: `${sales.total_revenue.toFixed(2)} total revenue`,
            status: 'healthy' as const,
        },
        {
            label: 'Low stock items',
            value: String(inventory.below_reorder_level_count),
            trend: inventory.below_reorder_level_count > 0 ? 'below reorder level' : 'all above reorder level',
            status: inventory.below_reorder_level_count > 0 ? ('attention' as const) : ('healthy' as const),
        },
    ];
});

const purchasingByStatus = computed(() => {
    if (!overview.value) {
        return [];
    }

    return Object.entries(overview.value.summary.purchasing.by_status).map(([label, value]) => ({ label, value }));
});

const salesByStatus = computed(() => {
    if (!overview.value) {
        return [];
    }

    return Object.entries(overview.value.summary.sales.by_status).map(([label, value]) => ({ label, value }));
});

const trendLabels = computed(() => overview.value?.trend.purchases.map((day) => day.date) ?? []);

const trendSeries = computed(() => {
    if (!overview.value) {
        return [];
    }

    return [
        {
            name: 'Purchase orders',
            color: '#0891b2',
            values: overview.value.trend.purchases.map((day) => day.count),
        },
        {
            name: 'Sales orders',
            color: '#7c3aed',
            values: overview.value.trend.sales_orders.map((day) => day.count),
        },
    ];
});
</script>

<template>
    <div>
        <div v-if="loadState === 'loading'" class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500">
            Loading dashboard…
        </div>

        <div v-else-if="loadState === 'error'" class="rounded-lg border border-rose-200 bg-rose-50 p-8 text-center text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <template v-else-if="overview">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="metric in metrics"
                    :key="metric.label"
                    class="rounded-lg border bg-white p-5 shadow-sm"
                    :class="statusClass[metric.status]"
                >
                    <p class="text-sm font-semibold">{{ metric.label }}</p>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <span class="text-3xl font-bold">{{ metric.value }}</span>
                        <span class="text-sm font-medium">{{ metric.trend }}</span>
                    </div>
                </article>
            </section>

            <section class="mt-6 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-zinc-950">
                    Order activity — last {{ overview.trend.days }} days
                </h3>
                <div class="mt-4">
                    <TrendLineChart :series="trendSeries" :labels="trendLabels" />
                </div>
            </section>

            <section class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-zinc-950">Purchase orders by status</h3>
                    <div class="mt-4">
                        <BarChart :data="purchasingByStatus" />
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-zinc-950">Sales orders by status</h3>
                    <div class="mt-4">
                        <BarChart :data="salesByStatus" bar-color-class="bg-violet-600" />
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>
