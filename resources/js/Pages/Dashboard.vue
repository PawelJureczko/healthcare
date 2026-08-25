<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    weight: { type: Object, required: true },
    health: { type: Object, required: true },
});

const weightForm = useForm({
    date: new Date().toISOString().slice(0, 10),
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
                            <span class="font-medium" :class="health.nextReminder.days_until_due < 0 ? 'text-red-600' : 'text-gray-900'">
                                {{ health.nextReminder.days_until_due < 0 ? 'zaległe' : `za ${health.nextReminder.days_until_due} dni` }}
                            </span>
                        </template>
                        <template v-else>Brak skonfigurowanych przypomnień o badaniach.</template>
                    </p>
                    <Link :href="route('reminders.index')" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Zarządzaj przypomnieniami →</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
