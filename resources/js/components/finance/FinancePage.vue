<script setup lang="ts">
import { ref } from 'vue';
import RecordPaymentForm from './RecordPaymentForm.vue';
import PaymentsTable from './PaymentsTable.vue';
import type { Payment } from '../../types/finance';

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
            <button
                type="button"
                class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800"
                @click="showForm = !showForm"
            >
                {{ showForm ? 'Close' : 'Record payment' }}
            </button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <div v-if="showForm" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-bold text-zinc-950">Record a payment</h3>
            <RecordPaymentForm @created="handleCreated" @cancel="showForm = false" />
        </div>

        <PaymentsTable :refresh-token="refreshToken" />
    </div>
</template>
