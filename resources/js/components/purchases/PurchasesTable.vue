<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { fetchPurchases, updatePurchaseStatus } from '../../api/purchases';
import { ApiValidationError } from '../../api/client';
import PurchaseStatusBadge from './PurchaseStatusBadge.vue';
import { PURCHASE_STATUSES } from '../../types/purchases';
import type { PaginationMeta, Purchase, PurchaseFilters, Supplier } from '../../types/purchases';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const props = defineProps<{ suppliers: Supplier[]; refreshToken: number }>();

const ALL_STATUSES = 'all';
const ALL_SUPPLIERS = 'all';

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

const statusModel = computed<string>({
    get: () => (filters.status === '' ? ALL_STATUSES : filters.status),
    set: (value) => {
        filters.status = value === ALL_STATUSES ? '' : (value as PurchaseFilters['status']);
    },
});

const supplierIdModel = computed<string>({
    get: () => (filters.supplierId === '' ? ALL_SUPPLIERS : String(filters.supplierId)),
    set: (value) => {
        filters.supplierId = value === ALL_SUPPLIERS ? '' : Number(value);
    },
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

const statusUpdateTarget = ref<Purchase | null>(null);
const statusUpdateValue = ref('');
const statusUpdateState = ref<'idle' | 'submitting' | 'error'>('idle');
const statusUpdateError = ref('');

const statusDialogOpen = computed({
    get: () => statusUpdateTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            statusUpdateTarget.value = null;
        }
    },
});

function openStatusUpdate(purchase: Purchase) {
    statusUpdateTarget.value = purchase;
    statusUpdateValue.value = purchase.allowed_next_statuses[0] ?? '';
    statusUpdateState.value = 'idle';
    statusUpdateError.value = '';
}

async function submitStatusUpdate() {
    const target = statusUpdateTarget.value;

    if (!target || !statusUpdateValue.value) {
        return;
    }

    statusUpdateState.value = 'submitting';
    statusUpdateError.value = '';

    try {
        await updatePurchaseStatus(target.id, statusUpdateValue.value as Purchase['status']);
        statusUpdateTarget.value = null;
        await load();
    } catch (error) {
        statusUpdateState.value = 'error';
        statusUpdateError.value = error instanceof ApiValidationError
            ? (error.errors.status?.[0] ?? error.message)
            : error instanceof Error
                ? error.message
                : 'Could not update the status.';
    }
}
</script>

<template>
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-bold text-zinc-950">Purchases</h3>

            <div class="flex flex-wrap gap-2">
                <Input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search reference or supplier"
                    class="w-56"
                />
                <Select v-model="statusModel">
                    <SelectTrigger class="w-40">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_STATUSES">All statuses</SelectItem>
                        <SelectItem v-for="status in PURCHASE_STATUSES" :key="status" :value="status">
                            {{ status }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="supplierIdModel">
                    <SelectTrigger class="w-48">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_SUPPLIERS">All suppliers</SelectItem>
                        <SelectItem v-for="supplier in props.suppliers" :key="supplier.id" :value="String(supplier.id)">
                            {{ supplier.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
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

        <Table v-else>
            <TableHeader>
                <TableRow>
                    <TableHead>Reference</TableHead>
                    <TableHead>Supplier</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Order date</TableHead>
                    <TableHead class="text-right">Total</TableHead>
                    <TableHead class="text-right">Balance due</TableHead>
                    <TableHead class="text-right">Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="purchase in purchases" :key="purchase.id">
                    <TableCell class="font-semibold text-zinc-900">{{ purchase.reference }}</TableCell>
                    <TableCell>{{ purchase.supplier?.name ?? '—' }}</TableCell>
                    <TableCell>
                        <PurchaseStatusBadge :status="purchase.status" />
                    </TableCell>
                    <TableCell>{{ purchase.order_date }}</TableCell>
                    <TableCell class="text-right font-semibold text-zinc-900">
                        {{ purchase.total_amount.toFixed(2) }}
                    </TableCell>
                    <TableCell class="text-right" :class="purchase.balance_due > 0 ? 'font-semibold text-amber-700' : 'text-emerald-700'">
                        {{ purchase.balance_due.toFixed(2) }}
                    </TableCell>
                    <TableCell class="text-right">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="purchase.allowed_next_statuses.length === 0"
                            :title="purchase.allowed_next_statuses.length === 0 ? 'No further status changes available' : undefined"
                            @click="openStatusUpdate(purchase)"
                        >
                            Update status
                        </Button>
                    </TableCell>
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

        <Dialog v-model:open="statusDialogOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Update purchase status</DialogTitle>
                </DialogHeader>
                <div v-if="statusUpdateTarget" class="space-y-4">
                    <p class="text-sm text-zinc-600">
                        {{ statusUpdateTarget.reference }} is currently
                        <span class="font-semibold capitalize">{{ statusUpdateTarget.status }}</span>.
                    </p>
                    <div v-if="statusUpdateError" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                        {{ statusUpdateError }}
                    </div>
                    <Select v-model="statusUpdateValue">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select a status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="status in statusUpdateTarget.allowed_next_statuses" :key="status" :value="status">
                                {{ status }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="statusDialogOpen = false">
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="statusUpdateState === 'submitting' || !statusUpdateValue"
                        @click="submitStatusUpdate"
                    >
                        {{ statusUpdateState === 'submitting' ? 'Saving…' : 'Save' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
