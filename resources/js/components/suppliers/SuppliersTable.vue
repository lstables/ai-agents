<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { deleteSupplier, fetchSuppliers } from '../../api/suppliers';
import { ApiError } from '../../api/client';
import type { PaginationMeta, Supplier } from '../../types/purchases';
import type { SupplierFilters } from '../../types/suppliers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
const emit = defineEmits<{ edit: [supplier: Supplier] }>();

const suppliers = ref<Supplier[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');
const deletingId = ref<number | null>(null);
const deleteError = ref('');
const supplierPendingDelete = ref<Supplier | null>(null);

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

function requestDelete(supplier: Supplier) {
    supplierPendingDelete.value = supplier;
}

async function confirmDelete() {
    const supplier = supplierPendingDelete.value;

    if (!supplier) {
        return;
    }

    // Close the dialog immediately: AlertDialogAction can't be used here
    // because Reka UI's AlertDialogAction is DialogClose under the hood, and
    // its template-bound onOpenChange(false) click handler always merges
    // ahead of an attrs-fallthrough @click (Vue merges root-vnode-authored
    // handlers before fallthrough attrs), so it would null
    // supplierPendingDelete before this function's body ever ran. Using a
    // plain Button here and closing explicitly avoids that ordering race.
    supplierPendingDelete.value = null;

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

            <Input
                v-model="filters.search"
                type="search"
                placeholder="Search name or email"
                class="md:w-64"
            />
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

        <Table v-else>
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Phone</TableHead>
                    <TableHead class="text-right">Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="supplier in suppliers" :key="supplier.id">
                    <TableCell class="font-semibold text-zinc-900">{{ supplier.name }}</TableCell>
                    <TableCell>{{ supplier.email ?? '—' }}</TableCell>
                    <TableCell>{{ supplier.phone ?? '—' }}</TableCell>
                    <TableCell class="text-right">
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" size="sm" @click="emit('edit', supplier)">
                                Edit
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-rose-200 text-rose-700 hover:bg-rose-50"
                                :disabled="deletingId === supplier.id"
                                @click="requestDelete(supplier)"
                            >
                                {{ deletingId === supplier.id ? 'Deleting…' : 'Delete' }}
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

        <AlertDialog :open="supplierPendingDelete !== null" @update:open="(open) => { if (!open) supplierPendingDelete = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete supplier?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Delete supplier "{{ supplierPendingDelete?.name }}"? This cannot be undone.
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
