<script setup lang="ts">
import { onMounted, ref } from 'vue';
import PurchaseCreateForm from './PurchaseCreateForm.vue';
import PurchasesTable from './PurchasesTable.vue';
import { fetchSuppliers } from '../../api/purchases';
import type { Purchase, Supplier } from '../../types/purchases';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const showCreateForm = ref(false);
const suppliers = ref<Supplier[]>([]);
const refreshToken = ref(0);
const confirmation = ref('');

async function loadSuppliers() {
    try {
        const response = await fetchSuppliers();
        suppliers.value = response.data;
    } catch {
        suppliers.value = [];
    }
}

onMounted(loadSuppliers);

function handleCreated(purchase: Purchase) {
    showCreateForm.value = false;
    confirmation.value = `Purchase ${purchase.reference} created.`;
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
                <p class="text-sm font-semibold text-cyan-700">Purchasing</p>
                <h2 class="mt-1 text-2xl font-bold text-zinc-950">Purchase orders</h2>
            </div>
            <Button type="button" @click="showCreateForm = true">
                New purchase
            </Button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <Dialog v-model:open="showCreateForm">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>New purchase</DialogTitle>
                </DialogHeader>
                <PurchaseCreateForm @created="handleCreated" />
            </DialogContent>
        </Dialog>

        <PurchasesTable :suppliers="suppliers" :refresh-token="refreshToken" />
    </div>
</template>
