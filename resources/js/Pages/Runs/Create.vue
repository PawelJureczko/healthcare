<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { localDate } from '@/localDateTime';

const form = useForm({
    date: localDate(),
    distance_km: '',
    duration_min: '',
    avg_heart_rate: '',
    comment: '',
    wellbeing_rating: '',
});

const submit = () => form.post(route('runs.store'));
</script>

<template>
    <Head title="Nowy bieg" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nowy bieg</h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div>
                        <InputLabel for="date" value="Data" />
                        <TextInput id="date" type="date" class="mt-1 block w-full" v-model="form.date" />
                        <InputError class="mt-2" :message="form.errors.date" />
                    </div>
                    <div>
                        <InputLabel for="distance_km" value="Dystans (km)" />
                        <TextInput id="distance_km" type="number" step="0.01" class="mt-1 block w-full" v-model="form.distance_km" />
                        <InputError class="mt-2" :message="form.errors.distance_km" />
                    </div>
                    <div>
                        <InputLabel for="duration_min" value="Czas (min)" />
                        <TextInput id="duration_min" type="number" step="0.1" class="mt-1 block w-full" v-model="form.duration_min" />
                        <InputError class="mt-2" :message="form.errors.duration_min" />
                    </div>
                    <div>
                        <InputLabel for="avg_heart_rate" value="Tętno śr. (opcjonalnie)" />
                        <TextInput id="avg_heart_rate" type="number" class="mt-1 block w-full" v-model="form.avg_heart_rate" />
                        <InputError class="mt-2" :message="form.errors.avg_heart_rate" />
                    </div>
                    <div>
                        <InputLabel for="wellbeing_rating" value="Samopoczucie 1-5 (opcjonalnie)" />
                        <TextInput id="wellbeing_rating" type="number" min="1" max="5" class="mt-1 block w-full" v-model="form.wellbeing_rating" />
                        <InputError class="mt-2" :message="form.errors.wellbeing_rating" />
                    </div>
                    <div>
                        <InputLabel for="comment" value="Komentarz (opcjonalnie)" />
                        <TextInput id="comment" type="text" class="mt-1 block w-full" v-model="form.comment" />
                        <InputError class="mt-2" :message="form.errors.comment" />
                    </div>
                    <PrimaryButton :disabled="form.processing">Zapisz bieg</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
