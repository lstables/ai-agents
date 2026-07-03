<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { deleteCustomer, fetchCustomers } from '../../api/customers';
import { ApiError } from '../../api/client';
import type { PaginationMeta } from '../../types/purchases';
import type { Customer, CustomerFilters } from '../../types/customers';
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
const emit = defineEmits<{ edit: [customer: Customer] }>();

const customers = ref<Customer[]>([]);
const meta = ref<PaginationMeta | null>(null);
const loadState = ref<'loading' | 'ready' | 'error'>('loading');
const errorMessage = ref('');
const deletingId = ref<number | null>(null);
const deleteError = ref('');
const customerPendingDelete = ref<Customer | null>(null);

const filters = reactive<CustomerFilters>({
    search: '',
    page: 1,
    perPage: 10,
});

let searchDebounce: ReturnType<typeof setTimeout> | undefined;

async function load() {
    loadState.value = 'loading';
    errorMessage.value = '';

    try {
        const response = await fetchCustomers(filters);
        customers.value = response.data;
        meta.value = response.meta;
        loadState.value = 'ready';
    } catch (error) {
        loadState.value = 'error';
        errorMessage.value = error instanceof Error ? error.message : 'Could not load customers.';
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

function requestDelete(customer: Customer) {
    customerPendingDelete.value = customer;
}

async function confirmDelete() {
    const customer = customerPendingDelete.value;

    if (!customer) {
        return;
    }

    // Close the dialog immediately: AlertDialogAction can't be used here
    // because Reka UI's AlertDialogAction is DialogClose under the hood, and
    // its template-bound onOpenChange(false) click handler always merges
    // ahead of an attrs-fallthrough @click (Vue merges root-vnode-authored
    // handlers before fallthrough attrs), so it would null
    // customerPendingDelete before this function's body ever ran. Using a
    // plain Button here and closing explicitly avoids that ordering race.
    customerPendingDelete.value = null;

    deletingId.value = customer.id;
    deleteError.value = '';

    try {
        await deleteCustomer(customer.id);
        await load();
    } catch (error) {
        deleteError.value = error instanceof ApiError || error instanceof Error
            ? error.message
            : 'Could not delete this customer.';
    } finally {
        deletingId.value = null;
    }
}
</script>

<template>
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-bold text-zinc-950">Customers</h3>

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
            Loading customers…
        </div>

        <div v-else-if="loadState === 'error'" class="px-5 py-8 text-center text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <div v-else-if="customers.length === 0" class="px-5 py-8 text-center text-sm text-zinc-500">
            No customers match this search.
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
                <TableRow v-for="customer in customers" :key="customer.id">
                    <TableCell class="font-semibold text-zinc-900">{{ customer.name }}</TableCell>
                    <TableCell>{{ customer.email ?? '—' }}</TableCell>
                    <TableCell>{{ customer.phone ?? '—' }}</TableCell>
                    <TableCell class="text-right">
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" size="sm" @click="emit('edit', customer)">
                                Edit
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-rose-200 text-rose-700 hover:bg-rose-50"
                                :disabled="deletingId === customer.id"
                                @click="requestDelete(customer)"
                            >
                                {{ deletingId === customer.id ? 'Deleting…' : 'Delete' }}
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

        <AlertDialog :open="customerPendingDelete !== null" @update:open="(open) => { if (!open) customerPendingDelete = null; }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete customer?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Delete customer "{{ customerPendingDelete?.name }}"? This cannot be undone.
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
