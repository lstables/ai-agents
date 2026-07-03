<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { deleteInventoryItem, fetchInventoryItems } from '../../api/inventory';
import { ApiError } from '../../api/client';
import type { PaginationMeta } from '../../types/purchases';
import type { InventoryItem, InventoryItemFilters } from '../../types/inventory';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const props = defineProps<{ refreshToken: number }>();
const emit = defineEmits<{ edit: [item: InventoryItem] }>();

const items = ref<InventoryItem[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');
const deletingId = ref<number | null>(null);
const deleteError = ref('');
const itemPendingDelete = ref<InventoryItem | null>(null);

const filters = reactive<InventoryItemFilters>({
    search: '',
    belowReorderLevel: false,
    page: 1,
    perPage: 10,
});

let searchDebounce: ReturnType<typeof setTimeout> | undefined;

async function load() {
    loadState.value = 'loading';
    errorMessage.value = '';

    try {
        const response = await fetchInventoryItems(filters);
        items.value = response.data;
        meta.value = response.meta;
        loadState.value = 'ready';
    } catch (error) {
        loadState.value = 'error';
        errorMessage.value = error instanceof Error ? error.message : 'Could not load inventory items.';
    }
}

onMounted(load);

watch(() => props.refreshToken, load);
watch(() => filters.belowReorderLevel, () => {
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

function requestDelete(item: InventoryItem) {
    itemPendingDelete.value = item;
}

async function confirmDelete() {
    const item = itemPendingDelete.value;

    if (!item) {
        return;
    }

    // Close the dialog immediately: AlertDialogAction can't be used here
    // because Reka UI's AlertDialogAction is DialogClose under the hood, and
    // its template-bound onOpenChange(false) click handler always merges
    // ahead of an attrs-fallthrough @click (Vue merges root-vnode-authored
    // handlers before fallthrough attrs), so it would null
    // itemPendingDelete before this function's body ever ran. Using a plain
    // Button here and closing explicitly avoids that ordering race.
    itemPendingDelete.value = null;

    deletingId.value = item.id;
    deleteError.value = '';

    try {
        await deleteInventoryItem(item.id);
        await load();
    } catch (error) {
        deleteError.value = error instanceof ApiError || error instanceof Error
            ? error.message
            : 'Could not delete this item.';
    } finally {
        deletingId.value = null;
    }
}
</script>

<template>
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-bold text-zinc-950">Inventory</h3>

            <div class="flex flex-wrap items-center gap-3">
                <Label class="flex items-center gap-2 text-sm font-medium text-zinc-700">
                    <Checkbox v-model:checked="filters.belowReorderLevel" />
                    Below reorder level
                </Label>
                <Input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search name or SKU"
                    class="md:w-56"
                />
            </div>
        </div>

        <div v-if="deleteError" class="border-b border-rose-200 bg-rose-50 px-5 py-2 text-sm text-rose-800">
            {{ deleteError }}
        </div>

        <div v-if="loadState === 'loading'" class="px-5 py-8 text-center text-sm text-zinc-500">
            Loading inventory…
        </div>

        <div v-else-if="loadState === 'error'" class="px-5 py-8 text-center text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <div v-else-if="items.length === 0" class="px-5 py-8 text-center text-sm text-zinc-500">
            No inventory items match these filters.
        </div>

        <Table v-else>
            <TableHeader>
                <TableRow>
                    <TableHead>SKU</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead class="text-right">Quantity</TableHead>
                    <TableHead class="text-right">Reorder level</TableHead>
                    <TableHead>Unit</TableHead>
                    <TableHead>Supplier</TableHead>
                    <TableHead class="text-right">Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="item in items" :key="item.id" :class="{ 'bg-amber-50': item.is_below_reorder_level }">
                    <TableCell class="font-semibold text-zinc-900">{{ item.sku }}</TableCell>
                    <TableCell>{{ item.name }}</TableCell>
                    <TableCell class="text-right">
                        {{ item.quantity_on_hand }}
                        <Badge v-if="item.is_below_reorder_level" variant="outline" class="ml-1 border-amber-200 bg-amber-100 text-amber-900">
                            Low
                        </Badge>
                    </TableCell>
                    <TableCell class="text-right">{{ item.reorder_level ?? '—' }}</TableCell>
                    <TableCell>{{ item.unit ?? '—' }}</TableCell>
                    <TableCell>{{ item.supplier?.name ?? '—' }}</TableCell>
                    <TableCell class="text-right">
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" size="sm" @click="emit('edit', item)">
                                Edit
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-rose-200 text-rose-700 hover:bg-rose-50"
                                :disabled="deletingId === item.id"
                                @click="requestDelete(item)"
                            >
                                {{ deletingId === item.id ? 'Deleting…' : 'Delete' }}
                            </Button>
                        </div>
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

        <AlertDialog :open="itemPendingDelete !== null" @update:open="(open) => { if (!open) itemPendingDelete = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete inventory item?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Delete inventory item "{{ itemPendingDelete?.name }}"? This cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <Button type="button" variant="destructive" @click="confirmDelete">
                        Delete
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
