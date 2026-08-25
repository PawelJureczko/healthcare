<script setup>
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale);

const props = defineProps({
    labels: { type: Array, required: true },
    datasets: { type: Array, required: true },
    height: { type: Number, default: 220 },
});

const chartData = () => ({
    labels: props.labels,
    datasets: props.datasets,
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: 'bottom' },
    },
    scales: {
        y: { beginAtZero: false },
    },
};
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Line :data="chartData()" :options="chartOptions" />
    </div>
</template>
