<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart from '@/Components/LineChart.vue';

const props = defineProps({
    workouts: { type: Array, required: true },
});

const backPainSeries = computed(() => {
    const rated = [...props.workouts].filter((w) => w.back_pain_rating !== null).reverse();
    return {
        labels: rated.map((w) => w.date),
        datasets: [{ label: 'Ból pleców (0-10)', data: rated.map((w) => w.back_pain_rating), borderColor: '#dc2626', tension: 0.2 }],
    };
});
</script>

<template>
    <Head title="Siłownia" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Siłownia</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <Link :href="route('gym-workouts.create')" class="text-sm text-indigo-600 hover:underline">+ Nowy trening</Link>
                </div>

                <div v-if="backPainSeries.labels.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Trend bólu pleców</h3>
                    <LineChart :labels="backPainSeries.labels" :datasets="backPainSeries.datasets" />
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Historia</h3>
                    <p v-if="workouts.length === 0" class="text-sm text-gray-600">Brak jeszcze żadnych treningów siłowych.</p>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2">Data</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2">Ból pleców</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="workout in workouts" :key="workout.id" class="border-t">
                                <td class="py-2">{{ workout.date }}</td>
                                <td class="py-2">{{ workout.status === 'planned' ? 'zaplanowany' : 'ukończony' }}</td>
                                <td class="py-2">{{ workout.back_pain_rating ?? '—' }}</td>
                                <td class="py-2">
                                    <Link :href="route('gym-workouts.show', workout.id)" class="text-indigo-600 hover:underline">
                                        {{ workout.status === 'planned' ? 'Rozpocznij' : 'Zobacz' }} →
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
