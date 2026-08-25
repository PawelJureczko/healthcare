<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart from '@/Components/LineChart.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { localDate } from '@/localDateTime';

const props = defineProps({
    measurements: { type: Array, default: () => [] },
    sevenDayAverages: { type: Array, default: () => [] },
    weightGoalKg: { type: [Number, String, null], default: null },
});

const form = useForm({
    date: localDate(),
    weight_kg: '',
    waist_cm: '',
});

const submit = () => {
    form.post(route('body-measurements.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('weight_kg', 'waist_cm'),
    });
};

const weightLabels = computed(() => props.measurements.map((m) => m.date));

const weightDatasets = computed(() => {
    const datasets = [
        {
            label: 'Waga (kg)',
            data: props.measurements.map((m) => Number(m.weight_kg)),
            borderColor: '#4f46e5',
            tension: 0.2,
        },
        {
            label: 'Średnia 7-dniowa',
            data: props.sevenDayAverages.map((value) => Number(value)),
            borderColor: '#059669',
            tension: 0.2,
            pointRadius: 0,
        },
    ];

    if (props.weightGoalKg) {
        datasets.push({
            label: 'Cel',
            data: props.measurements.map(() => Number(props.weightGoalKg)),
            borderColor: '#dc2626',
            borderDash: [6, 6],
            pointRadius: 0,
        });
    }

    return datasets;
});

const waistMeasurements = computed(() => props.measurements.filter((m) => m.waist_cm !== null));
const waistLabels = computed(() => waistMeasurements.value.map((m) => m.date));
const waistDatasets = computed(() => [
    {
        label: 'Obwód pasa (cm)',
        data: waistMeasurements.value.map((m) => Number(m.waist_cm)),
        borderColor: '#0891b2',
        tension: 0.2,
    },
]);
</script>

<template>
    <Head title="Ciało" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ciało</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nowy wpis</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <InputLabel for="date" value="Data" />
                            <TextInput id="date" type="date" class="mt-1 block w-full" v-model="form.date" max="9999-12-31" />
                            <InputError class="mt-2" :message="form.errors.date" />
                        </div>
                        <div>
                            <InputLabel for="weight_kg" value="Waga (kg)" />
                            <TextInput id="weight_kg" type="number" step="0.1" class="mt-1 block w-full" v-model="form.weight_kg" autofocus />
                            <InputError class="mt-2" :message="form.errors.weight_kg" />
                        </div>
                        <div>
                            <InputLabel for="waist_cm" value="Obwód pasa (cm, opcjonalnie)" />
                            <TextInput id="waist_cm" type="number" step="0.1" class="mt-1 block w-full" v-model="form.waist_cm" />
                            <InputError class="mt-2" :message="form.errors.waist_cm" />
                        </div>
                        <div class="sm:col-span-3">
                            <PrimaryButton :disabled="form.processing">Zapisz</PrimaryButton>
                            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-gray-600">Zapisano.</span>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Waga w czasie</h3>
                    <LineChart v-if="measurements.length" :labels="weightLabels" :datasets="weightDatasets" />
                    <p v-else class="text-sm text-gray-600">Brak jeszcze żadnych wpisów.</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Obwód pasa w czasie</h3>
                    <LineChart v-if="waistMeasurements.length" :labels="waistLabels" :datasets="waistDatasets" />
                    <p v-else class="text-sm text-gray-600">Brak jeszcze żadnych pomiarów obwodu pasa.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
