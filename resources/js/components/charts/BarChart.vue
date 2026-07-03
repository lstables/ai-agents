<script setup lang="ts">
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import type { ChartData, ChartOptions } from 'chart.js';
import './chartjs-setup';

type BarDatum = {
    label: string;
    value: number;
};

const props = defineProps<{
    data: BarDatum[];
    color?: string;
}>();

const chartData = computed<ChartData<'bar'>>(() => ({
    labels: props.data.map((datum) => datum.label),
    datasets: [
        {
            data: props.data.map((datum) => datum.value),
            backgroundColor: props.color ?? '#0891b2',
            borderRadius: 4,
        },
    ],
}));

const chartOptions: ChartOptions<'bar'> = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: { beginAtZero: true, ticks: { precision: 0 } },
    },
};
</script>

<template>
    <div class="h-56">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
