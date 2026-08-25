<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    markers: { type: Array, required: true },
});

const form = useForm({
    performed_at: new Date().toISOString().slice(0, 10),
    note: '',
    values: props.markers.map((marker) => ({ lab_marker_id: marker.id, value: '' })),
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        values: data.values.filter((v) => v.value !== '' && v.value !== null),
    })).post(route('lab-results.store'));
};

const showNewMarkerForm = ref(false);
const newMarkerForm = useForm({ name: '', unit: 'mg/dl', norm_min: '', norm_max: '' });

const submitNewMarker = () => {
    newMarkerForm.post(route('lab-markers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newMarkerForm.reset();
            showNewMarkerForm.value = false;
        },
    });
};
</script>

<template>
    <Head title="Nowe badanie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nowe badanie krwi</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="performed_at" value="Data wykonania" />
                            <TextInput id="performed_at" type="date" class="mt-1 block w-full" v-model="form.performed_at" />
                            <InputError class="mt-2" :message="form.errors.performed_at" />
                            <p class="mt-1 text-xs text-gray-500">Można wpisać dowolną datę wsteczną, żeby uzupełnić historię.</p>
                        </div>

                        <div v-for="(row, index) in form.values" :key="row.lab_marker_id" class="grid grid-cols-2 gap-4 items-end">
                            <InputLabel :value="`${markers[index].name} (${markers[index].unit})`" />
                            <div>
                                <TextInput type="number" step="0.01" class="mt-1 block w-full" v-model="row.value" />
                                <InputError class="mt-2" :message="form.errors[`values.${index}.value`]" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="note" value="Notatka (opcjonalnie)" />
                            <textarea id="note" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" v-model="form.note" rows="2"></textarea>
                        </div>

                        <PrimaryButton :disabled="form.processing">Zapisz badanie</PrimaryButton>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <button type="button" class="text-sm text-indigo-600 hover:underline" @click="showNewMarkerForm = !showNewMarkerForm">
                        + Dodaj własny marker
                    </button>

                    <form v-if="showNewMarkerForm" @submit.prevent="submitNewMarker" class="mt-4 grid grid-cols-2 gap-4 items-end">
                        <div>
                            <InputLabel for="new_marker_name" value="Nazwa" />
                            <TextInput id="new_marker_name" class="mt-1 block w-full" v-model="newMarkerForm.name" />
                            <InputError class="mt-2" :message="newMarkerForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="new_marker_unit" value="Jednostka" />
                            <TextInput id="new_marker_unit" class="mt-1 block w-full" v-model="newMarkerForm.unit" />
                        </div>
                        <div>
                            <InputLabel for="new_marker_norm_min" value="Norma min (opcjonalnie)" />
                            <TextInput id="new_marker_norm_min" type="number" step="0.01" class="mt-1 block w-full" v-model="newMarkerForm.norm_min" />
                        </div>
                        <div>
                            <InputLabel for="new_marker_norm_max" value="Norma max (opcjonalnie)" />
                            <TextInput id="new_marker_norm_max" type="number" step="0.01" class="mt-1 block w-full" v-model="newMarkerForm.norm_max" />
                            <InputError class="mt-2" :message="newMarkerForm.errors.norm_max" />
                        </div>
                        <div class="col-span-2">
                            <SecondaryButton :disabled="newMarkerForm.processing">Dodaj marker</SecondaryButton>
                            <p class="mt-2 text-xs text-gray-500">Nowy marker pojawi się przy następnym wejściu na tę stronę.</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
