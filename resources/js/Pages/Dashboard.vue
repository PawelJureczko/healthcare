<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { localDate } from '@/localDateTime';

const props = defineProps({
    weight: { type: Object, required: true },
    health: { type: Object, required: true },
    running: { type: Object, required: true },
    gym: { type: Object, required: true },
});

const weightForm = useForm({
    date: localDate(),
    weight_kg: '',
});

const submitWeight = () => {
    weightForm.post(route('body-measurements.store'), {
        preserveScroll: true,
        onSuccess: () => weightForm.reset('weight_kg'),
    });
};

const trendLabel = (trend) => {
    if (trend === null) return 'brak danych z zeszłego tygodnia';
    if (trend === 0) return 'bez zmian';
    return trend < 0 ? `↓ ${Math.abs(trend)} kg` : `↑ ${trend} kg`;
};

const syncStrava = () => router.post(route('strava.sync'));
</script>

<template>
    <Head title="Panel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Waga</h3>
                    <p v-if="weight.sevenDayAverage !== null" class="text-2xl font-semibold text-gray-900">
                        {{ weight.sevenDayAverage }} kg <span class="text-base font-normal text-gray-500">(śr. 7 dni)</span>
                    </p>
                    <p v-else class="text-sm text-gray-600">Brak jeszcze żadnych wpisów wagi.</p>
                    <p class="text-sm text-gray-600 mt-1">Trend tygodniowy: {{ trendLabel(weight.weeklyTrend) }}</p>
                    <p v-if="weight.distanceToGoal !== null" class="text-sm text-gray-600">
                        Dystans do celu: {{ weight.distanceToGoal > 0 ? '+' : '' }}{{ weight.distanceToGoal }} kg
                    </p>

                    <form @submit.prevent="submitWeight" class="mt-4 flex items-end gap-3">
                        <div>
                            <label for="quick_weight" class="sr-only">Waga dzisiaj (kg)</label>
                            <TextInput id="quick_weight" type="number" step="0.1" placeholder="Waga dzisiaj (kg)" v-model="weightForm.weight_kg" autofocus />
                            <InputError class="mt-2" :message="weightForm.errors.weight_kg" />
                        </div>
                        <PrimaryButton :disabled="weightForm.processing">Zapisz</PrimaryButton>
                        <span v-if="weightForm.recentlySuccessful" class="text-sm text-gray-600">Zapisano.</span>
                    </form>
                    <Link :href="route('body.index')" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Zobacz historię i wykresy →</Link>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Zdrowie</h3>
                    <p class="text-sm text-gray-600">
                        Ostatnie ciśnienie:
                        <span class="font-medium text-gray-900">{{ health.lastBloodPressure ?? 'brak wpisów' }}</span>
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        <template v-if="health.nextReminder">
                            Następne badanie ({{ health.nextReminder.type }}):
                            <span
                                class="font-medium"
                                :class="health.nextReminder.days_until_due === null || health.nextReminder.days_until_due < 0 ? 'text-red-600' : 'text-gray-900'"
                            >
                                <template v-if="health.nextReminder.days_until_due === null">nigdy nie wykonane</template>
                                <template v-else-if="health.nextReminder.days_until_due < 0">zaległe</template>
                                <template v-else>za {{ health.nextReminder.days_until_due }} dni</template>
                            </span>
                        </template>
                        <template v-else>Brak skonfigurowanych przypomnień o badaniach.</template>
                    </p>
                    <Link :href="route('reminders.index')" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Zarządzaj przypomnieniami →</Link>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Bieganie</h3>
                    <template v-if="running.activeGoal">
                        <p class="text-sm text-gray-600 mb-2">
                            {{ running.activeGoal.target_distance_km }} km do {{ running.activeGoal.target_date }}
                        </p>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: running.activeGoal.progressPercent + '%' }"></div>
                        </div>
                    </template>
                    <p v-else class="text-sm text-gray-600">Brak ustawionego celu biegowego.</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <button v-if="running.stravaConnected" @click="syncStrava" class="text-sm text-indigo-600 hover:underline">
                            Pobierz ze Stravy
                        </button>
                        <Link :href="route('runs.index')" class="text-sm text-indigo-600 hover:underline">Zobacz biegi →</Link>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Siłownia</h3>
                    <p class="text-sm text-gray-600">
                        Ostatnia ocena bólu pleców:
                        <span class="font-medium text-gray-900">{{ gym.lastBackPainRating ?? 'brak wpisów' }}</span>
                    </p>
                    <p v-if="gym.hasPlannedWorkout" class="text-sm text-indigo-600 mt-1">Masz zaplanowany trening.</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <Link :href="route('gym-workouts.create')" class="text-sm text-indigo-600 hover:underline">Rozpocznij trening →</Link>
                        <Link :href="route('gym-workouts.index')" class="text-sm text-indigo-600 hover:underline">Historia →</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
