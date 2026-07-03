<script setup lang="ts">
import { computed } from 'vue';
import type { SalesOrderStatus } from '../../types/sales-orders';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{ status: SalesOrderStatus }>();

const statusClass: Record<SalesOrderStatus, string> = {
    draft: 'border-zinc-300 bg-zinc-100 text-zinc-700',
    pending: 'border-amber-200 bg-amber-50 text-amber-900',
    confirmed: 'border-cyan-200 bg-cyan-50 text-cyan-900',
    fulfilled: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    cancelled: 'border-rose-200 bg-rose-50 text-rose-800',
};

const classes = computed(() => statusClass[props.status]);
</script>

<template>
    <Badge variant="outline" class="capitalize" :class="classes">
        {{ status }}
    </Badge>
</template>
