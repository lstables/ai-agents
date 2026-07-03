<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { deleteInventoryItem, fetchInventoryItems } from '../../api/inventory';
import { ApiError } from '../../api/client';
import type { PaginationMeta } from '../../types/purchases';
import type { InventoryItem, InventoryItemFilters } from '../../types/inventory';

const props = defineProps<{ refreshToken: number }>();
const emit = defineEmits<{ edit: [item: InventoryItem] }>();

const items = ref<InventoryItem[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');
const deletingId = ref<number | null>(null);
const deleteError = ref('');

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

async function remove(item: InventoryItem) {
    if (!confirm(`Delete inventory item "${item.name}"? This cannot be undone.`)) {
        return;
    }

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
                <label class="flex items-center gap-2 text-sm font-medium text-zinc-700">
                    <input v-model="filters.belowReorderLevel" type="checkbox" class="rounded border-zinc-300">
                    Below reorder level
                </label>
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search name or SKU"
                    class="rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
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

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                    <tr>
                        <th class="px-5 py-3">SKU</th>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3 text-right">Quantity</th>
                        <th class="px-5 py-3 text-right">Reorder level</th>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <tr v-for="item in items" :key="item.id" :class="{ 'bg-amber-50': item.is_below_reorder_level }">
                        <td class="px-5 py-3 font-semibold text-zinc-900">{{ item.sku }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ item.name }}</td>
                        <td class="px-5 py-3 text-right text-zinc-700">
                            {{ item.quantity_on_hand }}
                            <span
                                v-if="item.is_below_reorder_level"
                                class="ml-1 rounded-md border border-amber-200 bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900"
                            >
                                Low
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-zinc-700">{{ item.reorder_level ?? '—' }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ item.unit ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="rounded-md border border-zinc-300 px-3 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50"
                                    @click="emit('edit', item)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                                    :disabled="deletingId === item.id"
                                    @click="remove(item)"
                                >
                                    {{ deletingId === item.id ? 'Deleting…' : 'Delete' }}
                                </button>
                            </div>
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
