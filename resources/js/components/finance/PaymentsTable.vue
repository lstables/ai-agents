<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { fetchPayments } from '../../api/finance';
import { PAYABLE_TYPES, PAYABLE_TYPE_LABELS } from '../../types/finance';
import type { PaginationMeta } from '../../types/purchases';
import type { Payment, PaymentFilters } from '../../types/finance';

const props = defineProps<{ refreshToken: number }>();

const payments = ref<Payment[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');

const filters = reactive<PaymentFilters>({
    payableType: '',
    search: '',
    page: 1,
    perPage: 10,
});

let searchDebounce: ReturnType<typeof setTimeout> | undefined;

async function load() {
    loadState.value = 'loading';
    errorMessage.value = '';

    try {
        const response = await fetchPayments(filters);
        payments.value = response.data;
        meta.value = response.meta;
        loadState.value = 'ready';
    } catch (error) {
        loadState.value = 'error';
        errorMessage.value = error instanceof Error ? error.message : 'Could not load payments.';
    }
}

onMounted(load);

watch(() => props.refreshToken, load);
watch(() => filters.payableType, () => {
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
            <h3 class="text-lg font-bold text-zinc-950">Payments</h3>

            <div class="flex flex-wrap gap-2">
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search reference or order"
                    class="rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <select v-model="filters.payableType" class="rounded-md border border-zinc-300 px-3 py-2 text-sm">
                    <option value="">All types</option>
                    <option v-for="type in PAYABLE_TYPES" :key="type" :value="type">
                        {{ PAYABLE_TYPE_LABELS[type] }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="loadState === 'loading'" class="px-5 py-8 text-center text-sm text-zinc-500">
            Loading payments…
        </div>

        <div v-else-if="loadState === 'error'" class="px-5 py-8 text-center text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <div v-else-if="payments.length === 0" class="px-5 py-8 text-center text-sm text-zinc-500">
            No payments match these filters.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <tr v-for="payment in payments" :key="payment.id">
                        <td class="px-5 py-3 font-semibold text-zinc-900">{{ payment.payable?.reference ?? '—' }}</td>
                        <td class="px-5 py-3 text-zinc-700">
                            {{ payment.payable ? PAYABLE_TYPE_LABELS[payment.payable.type] : '—' }}
                        </td>
                        <td class="px-5 py-3 text-zinc-700">{{ payment.payment_date }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ payment.method ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-zinc-900">{{ payment.amount.toFixed(2) }}</td>
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
