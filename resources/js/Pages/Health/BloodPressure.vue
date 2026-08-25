<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart from '@/Components/LineChart.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    readings: { type: Array, default: () => [] },
});

const form = useForm({
    measured_at: new Date().toISOString().slice(0, 16),
    systolic: '',
    diastolic: '',
    resting_pulse: '',
});

const submit = () => {
    form.post(route('blood-pressure-readings.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('systolic', 'diastolic', 'resting_pulse'),
    });
};

const labels = computed(() => props.readings.map((r) => r.measured_at));
const datasets = computed(() => [
    { label: 'Skurczowe', data: props.readings.map((r) => r.systolic), borderColor: '#dc2626', tension: 0.2 },
    { label: 'Rozkurczowe', data: props.readings.map((r) => r.diastolic), borderColor: '#2563eb', tension: 0.2 },
]);
</script>

<template>
    <Head title="Ciśnienie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ciśnienie</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nowy pomiar</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                        <div>
                            <InputLabel for="measured_at" value="Data i godzina" />
                            <TextInput id="measured_at" type="datetime-local" class="mt-1 block w-full" v-model="form.measured_at" />
                            <InputError class="mt-2" :message="form.errors.measured_at" />
                        </div>
                        <div>
                            <InputLabel for="systolic" value="Skurczowe" />
                            <TextInput id="systolic" type="number" class="mt-1 block w-full" v-model="form.systolic" />
                            <InputError class="mt-2" :message="form.errors.systolic" />
                        </div>
                        <div>
                            <InputLabel for="diastolic" value="Rozkurczowe" />
                            <TextInput id="diastolic" type="number" class="mt-1 block w-full" v-model="form.diastolic" />
                            <InputError class="mt-2" :message="form.errors.diastolic" />
                        </div>
                        <div>
                            <InputLabel for="resting_pulse" value="Tętno spoczynkowe (opcjonalnie)" />
                            <TextInput id="resting_pulse" type="number" class="mt-1 block w-full" v-model="form.resting_pulse" />
                            <InputError class="mt-2" :message="form.errors.resting_pulse" />
                        </div>
                        <div class="sm:col-span-4">
                            <PrimaryButton :disabled="form.processing">Zapisz</PrimaryButton>
                            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-gray-600">Zapisano.</span>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Historia</h3>
                    <LineChart v-if="readings.length" :labels="labels" :datasets="datasets" />
                    <p v-else class="text-sm text-gray-600">Brak jeszcze żadnych pomiarów.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
