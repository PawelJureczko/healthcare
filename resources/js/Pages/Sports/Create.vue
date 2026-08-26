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
    sport_subtype: 'table_tennis',
    duration_min: '',
    intensity: '',
    comment: '',
});

const submit = () => form.post(route('sport-sessions.store'));
</script>

<template>
    <Head title="Nowy sport" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nowa sesja sportowa</h2>
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
                        <InputLabel for="sport_subtype" value="Dyscyplina" />
                        <select id="sport_subtype" v-model="form.sport_subtype" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="table_tennis">Tenis stołowy</option>
                            <option value="squash">Squash</option>
                            <option value="other">Inne</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.sport_subtype" />
                    </div>
                    <div>
                        <InputLabel for="duration_min" value="Czas trwania (min)" />
                        <TextInput id="duration_min" type="number" step="1" class="mt-1 block w-full" v-model="form.duration_min" />
                        <InputError class="mt-2" :message="form.errors.duration_min" />
                    </div>
                    <div>
                        <InputLabel for="intensity" value="Intensywność 1-5" />
                        <TextInput id="intensity" type="number" min="1" max="5" class="mt-1 block w-full" v-model="form.intensity" />
                        <InputError class="mt-2" :message="form.errors.intensity" />
                    </div>
                    <div>
                        <InputLabel for="comment" value="Komentarz (opcjonalnie)" />
                        <TextInput id="comment" type="text" class="mt-1 block w-full" v-model="form.comment" />
                        <InputError class="mt-2" :message="form.errors.comment" />
                    </div>
                    <PrimaryButton :disabled="form.processing">Zapisz</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
