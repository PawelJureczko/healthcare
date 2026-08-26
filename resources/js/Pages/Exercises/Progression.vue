<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart from '@/Components/LineChart.vue';

const props = defineProps({
    exercise: { type: Object, required: true },
    sessions: { type: Array, required: true },
});

const estimatedOneRepMax = (weightKg, reps) => Math.round(weightKg * (1 + reps / 30));

const chartLabels = computed(() => props.sessions.map((s) => s.date));
const chartDatasets = computed(() => [
    { label: 'Ciężar (kg)', data: props.sessions.map((s) => s.maxWeightKg), borderColor: '#4f46e5', tension: 0.2 },
]);
</script>

<template>
    <Head :title="`Progresja — ${exercise.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Progresja — {{ exercise.name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <Link :href="route('exercises.index')" class="text-sm text-indigo-600 hover:underline">← Wróć do słownika</Link>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p v-if="sessions.length === 0" class="text-sm text-gray-600">
                        Brak jeszcze ukończonych serii tego ćwiczenia.
                    </p>
                    <LineChart v-else :labels="chartLabels" :datasets="chartDatasets" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
