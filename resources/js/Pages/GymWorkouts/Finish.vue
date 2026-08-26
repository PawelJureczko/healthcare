<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    workoutId: { type: Number, required: true },
});

const form = useForm({ back_pain_rating: '', wellbeing_rating: '', comment: '' });
const submit = () => form.post(route('gym-workouts.finish', props.workoutId));
</script>

<template>
    <Head title="Zakończ trening" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Zakończ trening</h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div>
                        <InputLabel for="back_pain_rating" value="Ból pleców (0-10)" />
                        <TextInput id="back_pain_rating" type="number" min="0" max="10" class="mt-1 block w-full" v-model="form.back_pain_rating" />
                        <InputError class="mt-2" :message="form.errors.back_pain_rating" />
                    </div>
                    <div>
                        <InputLabel for="wellbeing_rating" value="Samopoczucie (1-5)" />
                        <TextInput id="wellbeing_rating" type="number" min="1" max="5" class="mt-1 block w-full" v-model="form.wellbeing_rating" />
                        <InputError class="mt-2" :message="form.errors.wellbeing_rating" />
                    </div>
                    <div>
                        <InputLabel for="comment" value="Komentarz (opcjonalnie)" />
                        <TextInput id="comment" type="text" class="mt-1 block w-full" v-model="form.comment" />
                        <InputError class="mt-2" :message="form.errors.comment" />
                    </div>
                    <PrimaryButton :disabled="form.processing">Zapisz i zakończ</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
