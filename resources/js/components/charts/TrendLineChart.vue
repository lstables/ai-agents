<script setup lang="ts">
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import type { ChartData, ChartOptions } from 'chart.js';
import './chartjs-setup';

type Series = {
    name: string;
    color: string;
    values: number[];
};

const props = defineProps<{
    series: Series[];
    labels: string[];
}>();

const chartData = computed<ChartData<'line'>>(() => ({
    labels: props.labels,
    datasets: props.series.map((series) => ({
        label: series.name,
        data: series.values,
        borderColor: series.color,
        backgroundColor: series.color,
        tension: 0.2,
        pointRadius: 0,
    })),
}));

const chartOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: 'bottom' },
    },
    scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } },
        x: { ticks: { maxTicksLimit: 6 } },
    },
};
</script>

<template>
    <div class="h-56">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
