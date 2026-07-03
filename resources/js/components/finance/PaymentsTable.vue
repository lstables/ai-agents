<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { fetchPayments } from '../../api/finance';
import { PAYABLE_TYPES, PAYABLE_TYPE_LABELS } from '../../types/finance';
import type { PaginationMeta } from '../../types/purchases';
import type { Payment, PaymentFilters } from '../../types/finance';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ refreshToken: number }>();

const ALL_TYPES = 'all';

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

const payableTypeModel = computed<string>({
    get: () => (filters.payableType === '' ? ALL_TYPES : filters.payableType),
    set: (value) => {
        filters.payableType = value === ALL_TYPES ? '' : (value as PaymentFilters['payableType']);
    },
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
                <Input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search reference or order"
                    class="w-56"
                />
                <Select v-model="payableTypeModel">
                    <SelectTrigger class="w-40">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_TYPES">All types</SelectItem>
                        <SelectItem v-for="type in PAYABLE_TYPES" :key="type" :value="type">
                            {{ PAYABLE_TYPE_LABELS[type] }}
                        </SelectItem>
                    </SelectContent>
                </Select>
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

        <Table v-else>
            <TableHeader>
                <TableRow>
                    <TableHead>Order</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Method</TableHead>
                    <TableHead class="text-right">Amount</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="payment in payments" :key="payment.id">
                    <TableCell class="font-semibold text-zinc-900">{{ payment.payable?.reference ?? '—' }}</TableCell>
                    <TableCell>
                        {{ payment.payable ? PAYABLE_TYPE_LABELS[payment.payable.type] : '—' }}
                    </TableCell>
                    <TableCell>{{ payment.payment_date }}</TableCell>
                    <TableCell>{{ payment.method ?? '—' }}</TableCell>
                    <TableCell class="text-right font-semibold text-zinc-900">{{ payment.amount.toFixed(2) }}</TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <div
            v-if="meta && meta.last_page > 1"
            class="flex items-center justify-between border-t border-zinc-200 px-5 py-3 text-sm text-zinc-600"
        >
            <span>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)</span>
            <div class="flex gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="meta.current_page <= 1"
                    @click="goToPage(meta.current_page - 1)"
                >
                    Previous
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="goToPage(meta.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>
