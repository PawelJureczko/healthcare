<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart from '@/Components/LineChart.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    results: { type: Array, default: () => [] },
    markers: { type: Array, default: () => [] },
});

const markersWithHistory = computed(() =>
    props.markers
        .map((marker) => {
            const points = props.results
                .flatMap((result) =>
                    result.values
                        .filter((v) => v.lab_marker_id === marker.id)
                        .map((v) => ({ date: result.performed_at, value: Number(v.value) }))
                )
                .sort((a, b) => a.date.localeCompare(b.date));

            return { marker, points };
        })
        .filter((entry) => entry.points.length > 0)
);

const datasetsFor = (entry) => {
    const datasets = [
        {
            label: `${entry.marker.name} (${entry.marker.unit})`,
            data: entry.points.map((p) => p.value),
            borderColor: '#4f46e5',
            tension: 0.2,
        },
    ];

    if (entry.marker.norm_max !== null) {
        datasets.push({
            label: 'Norma max',
            data: entry.points.map(() => Number(entry.marker.norm_max)),
            borderColor: '#dc2626',
            borderDash: [6, 6],
            pointRadius: 0,
        });
    }

    if (entry.marker.norm_min !== null) {
        datasets.push({
            label: 'Norma min',
            data: entry.points.map(() => Number(entry.marker.norm_min)),
            borderColor: '#f59e0b',
            borderDash: [6, 6],
            pointRadius: 0,
        });
    }

    return datasets;
};
</script>

<template>
    <Head title="Badania krwi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Badania krwi</h2>
                <Link :href="route('lab-results.create')">
                    <PrimaryButton>+ Nowe badanie</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <p class="text-xs text-gray-500">
                    Zaznaczone normy to ogólne wartości referencyjne — nie stanowią porady lekarskiej.
                </p>

                <div v-if="!markersWithHistory.length" class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                    Brak jeszcze żadnych wyników. Kliknij „Nowe badanie", żeby dodać pierwsze — także z datą wsteczną.
                </div>

                <div v-for="entry in markersWithHistory" :key="entry.marker.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ entry.marker.name }}</h3>
                    <LineChart :labels="entry.points.map((p) => p.date)" :datasets="datasetsFor(entry)" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
