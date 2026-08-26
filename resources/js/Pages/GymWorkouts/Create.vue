<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { localDate } from '@/localDateTime';

const props = defineProps({
    exercises: { type: Array, required: true },
    lastWeights: { type: Object, required: true },
});

const form = useForm({
    date: localDate(),
    exercises: [],
});

const addExercise = () => {
    form.exercises.push({ exercise_id: props.exercises[0]?.id ?? null, sets: [{ planned_weight_kg: '', planned_reps: '' }] });
};

const addSet = (exerciseIndex) => {
    form.exercises[exerciseIndex].sets.push({ planned_weight_kg: '', planned_reps: '' });
};

const removeExercise = (exerciseIndex) => form.exercises.splice(exerciseIndex, 1);
const removeSet = (exerciseIndex, setIndex) => form.exercises[exerciseIndex].sets.splice(setIndex, 1);

const lumbarRiskWarning = (exerciseId) => props.exercises.find((e) => e.id === exerciseId)?.lumbar_risk ?? false;
const lastWeightFor = (exerciseId) => props.lastWeights[exerciseId] ?? null;

const submit = () => form.post(route('gym-workouts.store'));
</script>

<template>
    <Head title="Nowy trening siłowy" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nowy trening siłowy</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <div>
                        <InputLabel for="date" value="Data" />
                        <TextInput id="date" type="date" class="mt-1 block w-full" v-model="form.date" />
                        <InputError class="mt-2" :message="form.errors.date" />
                    </div>

                    <InputError :message="form.errors.exercises" />

                    <div v-for="(exerciseEntry, exerciseIndex) in form.exercises" :key="exerciseIndex" class="border rounded-lg p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <select v-model="exerciseEntry.exercise_id" class="block w-full border-gray-300 rounded-md shadow-sm">
                                <option v-for="exercise in exercises" :key="exercise.id" :value="exercise.id">{{ exercise.name }}</option>
                            </select>
                            <SecondaryButton type="button" @click="removeExercise(exerciseIndex)">Usuń</SecondaryButton>
                        </div>
                        <p v-if="lumbarRiskWarning(exerciseEntry.exercise_id)" class="text-sm text-red-600">
                            ⚠️ Ćwiczenie oznaczone jako ryzykowne dla odcinka lędźwiowego — zacznij od lekkiego ciężaru.
                        </p>
                        <p v-if="lastWeightFor(exerciseEntry.exercise_id)" class="text-xs text-gray-500">
                            Ostatnio: {{ lastWeightFor(exerciseEntry.exercise_id) }} kg
                        </p>

                        <div v-for="(set, setIndex) in exerciseEntry.sets" :key="setIndex" class="flex items-center gap-3">
                            <TextInput type="number" step="0.5" placeholder="Ciężar (kg)" v-model="set.planned_weight_kg" class="w-28" />
                            <TextInput type="number" placeholder="Powtórzenia" v-model="set.planned_reps" class="w-28" />
                            <SecondaryButton type="button" @click="removeSet(exerciseIndex, setIndex)">Usuń serię</SecondaryButton>
                        </div>
                        <SecondaryButton type="button" @click="addSet(exerciseIndex)">+ Seria</SecondaryButton>
                    </div>

                    <SecondaryButton type="button" @click="addExercise">+ Ćwiczenie</SecondaryButton>

                    <div>
                        <PrimaryButton :disabled="form.processing">Rozpocznij trening</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
