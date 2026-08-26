<script setup>
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import LineChart from '@/Components/LineChart.vue';

const props = defineProps({
    runs: { type: Array, required: true },
    activeGoal: { type: Object, default: null },
    stravaConnected: { type: Boolean, required: true },
});

const chartLabels = computed(() => props.runs.map((r) => r.date));
const chartDatasets = computed(() => [
    { label: 'Dystans (km)', data: props.runs.map((r) => r.distance_km), borderColor: '#4f46e5', tension: 0.2 },
]);

const syncStrava = () => router.post(route('strava.sync'));

const goalForm = useForm({ target_distance_km: '', target_date: '' });
const submitGoal = () => goalForm.post(route('training-goals.store'), { onSuccess: () => goalForm.reset() });
</script>

<template>
    <Head title="Bieganie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bieganie</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-wrap items-center gap-3">
                    <PrimaryButton v-if="stravaConnected" @click="syncStrava">Pobierz ze Stravy</PrimaryButton>
                    <Link v-else :href="route('profile.edit')" class="text-sm text-indigo-600 hover:underline">
                        Połącz Stravę, aby importować biegi automatycznie
                    </Link>
                    <Link :href="route('runs.create')" class="text-sm text-indigo-600 hover:underline">Dodaj bieg ręcznie →</Link>
                </div>

                <div v-if="activeGoal" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Cel biegowy</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        {{ activeGoal.target_distance_km }} km do {{ activeGoal.target_date }}
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: activeGoal.progressPercent + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ activeGoal.progressPercent }}% — na podstawie najdłuższego biegu</p>
                </div>
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Ustaw cel biegowy</h3>
                    <form @submit.prevent="submitGoal" class="flex flex-wrap items-end gap-3">
                        <div>
                            <InputLabel for="target_distance_km" value="Dystans (km)" />
                            <TextInput id="target_distance_km" type="number" step="0.1" v-model="goalForm.target_distance_km" />
                            <InputError :message="goalForm.errors.target_distance_km" />
                        </div>
                        <div>
                            <InputLabel for="target_date" value="Data" />
                            <TextInput id="target_date" type="date" v-model="goalForm.target_date" />
                            <InputError :message="goalForm.errors.target_date" />
                        </div>
                        <PrimaryButton :disabled="goalForm.processing">Ustaw cel</PrimaryButton>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Historia</h3>
                    <p v-if="runs.length === 0" class="text-sm text-gray-600">Brak jeszcze żadnych biegów.</p>
                    <template v-else>
                        <LineChart :labels="chartLabels" :datasets="chartDatasets" class="mb-6" />
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="pb-2">Data</th>
                                    <th class="pb-2">Dystans</th>
                                    <th class="pb-2">Czas</th>
                                    <th class="pb-2">Tętno śr.</th>
                                    <th class="pb-2">Źródło</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="run in runs" :key="run.id" class="border-t">
                                    <td class="py-2">{{ run.date }}</td>
                                    <td class="py-2">{{ run.distance_km }} km</td>
                                    <td class="py-2">{{ run.duration_min }} min</td>
                                    <td class="py-2">{{ run.avg_heart_rate ?? '—' }}</td>
                                    <td class="py-2">{{ run.source === 'strava' ? 'Strava' : 'ręczny' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
