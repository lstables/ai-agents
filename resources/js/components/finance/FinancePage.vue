<script setup lang="ts">
import { ref } from 'vue';
import RecordPaymentForm from './RecordPaymentForm.vue';
import PaymentsTable from './PaymentsTable.vue';
import type { Payment } from '../../types/finance';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const showForm = ref(false);
const refreshToken = ref(0);
const confirmation = ref('');

function handleCreated(payment: Payment) {
    showForm.value = false;
    confirmation.value = `Payment of ${payment.amount.toFixed(2)} recorded against ${payment.payable?.reference ?? 'the order'}.`;
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
                <p class="text-sm font-semibold text-cyan-700">Finance</p>
                <h2 class="mt-1 text-2xl font-bold text-zinc-950">Payments</h2>
            </div>
            <Button type="button" @click="showForm = true">
                Record payment
            </Button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <Dialog v-model:open="showForm">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Record a payment</DialogTitle>
                </DialogHeader>
                <RecordPaymentForm @created="handleCreated" @cancel="showForm = false" />
            </DialogContent>
        </Dialog>

        <PaymentsTable :refresh-token="refreshToken" />
    </div>
</template>
