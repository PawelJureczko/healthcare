<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    sessions: { type: Array, required: true },
});

const subtypeLabel = (subtype) => ({ table_tennis: 'Tenis stołowy', squash: 'Squash', other: 'Inne' }[subtype] ?? subtype);
</script>

<template>
    <Head title="Sporty" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sporty</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Historia</h3>
                        <Link :href="route('sport-sessions.create')" class="text-sm text-indigo-600 hover:underline">Dodaj sesję →</Link>
                    </div>
                    <p v-if="sessions.length === 0" class="text-sm text-gray-600">Brak jeszcze żadnych sesji sportowych.</p>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2">Data</th>
                                <th class="pb-2">Dyscyplina</th>
                                <th class="pb-2">Czas</th>
                                <th class="pb-2">Intensywność</th>
                                <th class="pb-2">Źródło</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="session in sessions" :key="session.id" class="border-t">
                                <td class="py-2">{{ session.date }}</td>
                                <td class="py-2">{{ subtypeLabel(session.sport_subtype) }}</td>
                                <td class="py-2">{{ session.duration_min }} min</td>
                                <td class="py-2">{{ session.intensity !== null ? `${session.intensity}/5` : '—' }}</td>
                                <td class="py-2">{{ session.source === 'strava' ? 'Strava' : 'ręczny' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
