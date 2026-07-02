<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { deleteSupplier, fetchSuppliers } from '../../api/suppliers';
import { ApiError } from '../../api/client';
import type { PaginationMeta, Supplier } from '../../types/purchases';
import type { SupplierFilters } from '../../types/suppliers';

const props = defineProps<{ refreshToken: number }>();
const emit = defineEmits<{ edit: [supplier: Supplier] }>();

const suppliers = ref<Supplier[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');
const deletingId = ref<number | null>(null);
const deleteError = ref('');

const filters = reactive<SupplierFilters>({
    search: '',
    page: 1,
    perPage: 10,
});

let searchDebounce: ReturnType<typeof setTimeout> | undefined;

async function load() {
    loadState.value = 'loading';
    errorMessage.value = '';

    try {
        const response = await fetchSuppliers(filters);
        suppliers.value = response.data;
        meta.value = response.meta;
        loadState.value = 'ready';
    } catch (error) {
        loadState.value = 'error';
        errorMessage.value = error instanceof Error ? error.message : 'Could not load suppliers.';
    }
}

onMounted(load);

watch(() => props.refreshToken, load);
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

async function remove(supplier: Supplier) {
    if (!confirm(`Delete supplier "${supplier.name}"? This cannot be undone.`)) {
        return;
    }

    deletingId.value = supplier.id;
    deleteError.value = '';

    try {
        await deleteSupplier(supplier.id);
        await load();
    } catch (error) {
        deleteError.value = error instanceof ApiError || error instanceof Error
            ? error.message
            : 'Could not delete this supplier.';
    } finally {
        deletingId.value = null;
    }
}
</script>

<template>
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-bold text-zinc-950">Suppliers</h3>

            <input
                v-model="filters.search"
                type="search"
                placeholder="Search name or email"
                class="rounded-md border border-zinc-300 px-3 py-2 text-sm"
            >
        </div>

        <div v-if="deleteError" class="border-b border-rose-200 bg-rose-50 px-5 py-2 text-sm text-rose-800">
            {{ deleteError }}
        </div>

        <div v-if="loadState === 'loading'" class="px-5 py-8 text-center text-sm text-zinc-500">
            Loading suppliers…
        </div>

        <div v-else-if="loadState === 'error'" class="px-5 py-8 text-center text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <div v-else-if="suppliers.length === 0" class="px-5 py-8 text-center text-sm text-zinc-500">
            No suppliers match this search.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Phone</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <tr v-for="supplier in suppliers" :key="supplier.id">
                        <td class="px-5 py-3 font-semibold text-zinc-900">{{ supplier.name }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ supplier.email ?? '—' }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ supplier.phone ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="rounded-md border border-zinc-300 px-3 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50"
                                    @click="emit('edit', supplier)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                                    :disabled="deletingId === supplier.id"
                                    @click="remove(supplier)"
                                >
                                    {{ deletingId === supplier.id ? 'Deleting…' : 'Delete' }}
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
