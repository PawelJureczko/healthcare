<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    exercises: { type: Array, required: true },
});

const form = useForm({ name: '', muscle_group: '', lumbar_risk: false });
const submit = () => form.post(route('exercises.store'), { onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Ćwiczenia" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ćwiczenia</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dodaj własne ćwiczenie</h3>
                    <form @submit.prevent="submit" class="flex flex-wrap items-end gap-3">
                        <div>
                            <InputLabel for="name" value="Nazwa" />
                            <TextInput id="name" type="text" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="muscle_group" value="Partia mięśniowa" />
                            <TextInput id="muscle_group" type="text" v-model="form.muscle_group" />
                            <InputError :message="form.errors.muscle_group" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" v-model="form.lumbar_risk" class="rounded border-gray-300" />
                            Ryzyko dla lędźwi
                        </label>
                        <PrimaryButton :disabled="form.processing">Dodaj</PrimaryButton>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Słownik ćwiczeń</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2">Nazwa</th>
                                <th class="pb-2">Partia</th>
                                <th class="pb-2">Ryzyko lędźwi</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="exercise in exercises" :key="exercise.id" class="border-t">
                                <td class="py-2">{{ exercise.name }}</td>
                                <td class="py-2">{{ exercise.muscle_group }}</td>
                                <td class="py-2">
                                    <span v-if="exercise.lumbar_risk" class="text-red-600">tak</span>
                                    <span v-else class="text-gray-400">nie</span>
                                </td>
                                <td class="py-2">
                                    <Link :href="route('exercises.progression', exercise.id)" class="text-indigo-600 hover:underline">
                                        Progresja →
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
