<script setup lang="ts">
import { computed } from 'vue';

type BarDatum = {
    label: string;
    value: number;
};

const props = defineProps<{
    data: BarDatum[];
    barColorClass?: string;
    valueFormatter?: (value: number) => string;
}>();

const maxValue = computed(() => Math.max(1, ...props.data.map((datum) => datum.value)));

function widthPercent(value: number): number {
    return Math.round((value / maxValue.value) * 100);
}

function formattedValue(value: number): string {
    return props.valueFormatter ? props.valueFormatter(value) : String(value);
}
</script>

<template>
    <div class="space-y-2">
        <div v-for="datum in data" :key="datum.label" class="grid grid-cols-[100px_1fr_60px] items-center gap-3 text-sm">
            <span class="truncate capitalize text-zinc-600" :title="datum.label">{{ datum.label }}</span>
            <div class="h-3 rounded-full bg-zinc-100">
                <div
                    class="h-3 rounded-full"
                    :class="barColorClass ?? 'bg-cyan-600'"
                    :style="{ width: `${widthPercent(datum.value)}%` }"
                />
            </div>
            <span class="text-right font-semibold text-zinc-900">{{ formattedValue(datum.value) }}</span>
        </div>
    </div>
</template>
