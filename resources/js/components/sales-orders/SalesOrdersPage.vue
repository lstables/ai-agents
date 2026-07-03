<script setup lang="ts">
import { onMounted, ref } from 'vue';
import SalesOrderCreateForm from './SalesOrderCreateForm.vue';
import SalesOrdersTable from './SalesOrdersTable.vue';
import { fetchAllCustomers } from '../../api/customers';
import type { Customer } from '../../types/customers';
import type { SalesOrder } from '../../types/sales-orders';

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
            <button
                type="button"
                class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800"
                @click="showCreateForm = !showCreateForm"
            >
                {{ showCreateForm ? 'Close' : 'New sales order' }}
            </button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <div v-if="showCreateForm" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-bold text-zinc-950">New sales order</h3>
            <SalesOrderCreateForm @created="handleCreated" />
        </div>

        <SalesOrdersTable :customers="customers" :refresh-token="refreshToken" />
    </div>
</template>
