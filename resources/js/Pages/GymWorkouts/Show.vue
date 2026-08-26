<script setup>
import { onMounted, onUnmounted, reactive, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { updateGymSet, drainQueue } from '@/gymOfflineQueue';

const props = defineProps({
    workout: { type: Object, required: true },
});

const gymExercises = reactive(structuredClone(props.workout.gymExercises));
const restSecondsRemaining = ref(0);
let restInterval = null;

const startRestTimer = (seconds = 90) => {
    clearInterval(restInterval);
    restSecondsRemaining.value = seconds;
    restInterval = setInterval(() => {
        if (restSecondsRemaining.value > 0) {
            restSecondsRemaining.value--;
        } else {
            clearInterval(restInterval);
        }
    }, 1000);
};

const markDone = async (set) => {
    set.status = 'done'; // optimistic
    await updateGymSet(set.id, { weight_kg: set.weight_kg, reps: set.reps, status: 'done' });
    startRestTimer();
};

onMounted(() => {
    drainQueue();
    window.addEventListener('online', drainQueue);
});

onUnmounted(() => {
    window.removeEventListener('online', drainQueue);
    clearInterval(restInterval);
});
</script>

<template>
    <Head title="Trening siłowy" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trening siłowy — {{ workout.date }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-if="restSecondsRemaining > 0" class="bg-indigo-50 text-indigo-800 rounded-lg p-4 text-center text-2xl font-semibold">
                    Przerwa: {{ restSecondsRemaining }}s
                </div>

                <div v-for="gymExercise in gymExercises" :key="gymExercise.id" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">{{ gymExercise.exercise.name }}</h3>
                    <p v-if="gymExercise.exercise.lumbar_risk" class="text-sm text-red-600 mb-2">⚠️ Ryzyko dla lędźwi — zachowaj ostrożność.</p>
                    <p v-if="gymExercise.lastWeight" class="text-xs text-gray-500 mb-3">Ostatnio: {{ gymExercise.lastWeight }} kg</p>

                    <div v-for="set in gymExercise.gymSets" :key="set.id" class="flex items-center gap-3 py-2 border-t first:border-t-0">
                        <span class="w-8 text-sm text-gray-500">#{{ set.set_number }}</span>
                        <span class="text-sm text-gray-500 w-32">plan: {{ set.planned_weight_kg ?? '—' }} kg × {{ set.planned_reps }}</span>
                        <TextInput type="number" step="0.5" placeholder="kg" v-model="set.weight_kg" class="w-24" />
                        <TextInput type="number" placeholder="powt." v-model="set.reps" class="w-24" />
                        <PrimaryButton v-if="set.status !== 'done'" @click="markDone(set)" class="text-lg py-3 px-6">✓ Zrobione</PrimaryButton>
                        <span v-else class="text-green-600 font-semibold">✓ Zrobione</span>
                    </div>
                </div>

                <Link
                    :href="route('gym-workouts.finish.form', workout.id)"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Zakończ trening →
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
