<script setup lang="ts">
import { onMounted, ref } from 'vue';
import SalesOrderCreateForm from './SalesOrderCreateForm.vue';
import SalesOrdersTable from './SalesOrdersTable.vue';
import { fetchAllCustomers } from '../../api/customers';
import type { Customer } from '../../types/customers';
import type { SalesOrder } from '../../types/sales-orders';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const showCreateForm = ref(false);
const customers = ref<Customer[]>([]);
const refreshToken = ref(0);
const confirmation = ref('');

async function loadCustomers() {
    try {
        const response = await fetchAllCustomers();
        customers.value = response.data;
    } catch {
        customers.value = [];
    }
}

onMounted(loadCustomers);

function handleCreated(salesOrder: SalesOrder) {
    showCreateForm.value = false;
    confirmation.value = `Sales order ${salesOrder.reference} created.`;
    refreshToken.value += 1;

    setTimeout(() => {
        confirmation.value = '';
    }, 4000);
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-cyan-700">Sales Orders</p>
                <h2 class="mt-1 text-2xl font-bold text-zinc-950">Sales orders</h2>
            </div>
            <Button type="button" @click="showCreateForm = true">
                New sales order
            </Button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <Dialog v-model:open="showCreateForm">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>New sales order</DialogTitle>
                </DialogHeader>
                <SalesOrderCreateForm @created="handleCreated" />
            </DialogContent>
        </Dialog>

        <SalesOrdersTable :customers="customers" :refresh-token="refreshToken" />
    </div>
</template>
