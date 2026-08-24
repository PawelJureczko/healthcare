<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    profile: Object,
});

const form = useForm({
    age: props.profile?.age ?? null,
    height_cm: props.profile?.height_cm ?? null,
    weight_goal_kg: props.profile?.weight_goal_kg ?? null,
    injuries: props.profile?.injuries ?? '',
    dietary_preferences: props.profile?.dietary_preferences ?? '',
});

const submit = () => {
    form.patch(route('profile.details.update'), { preserveScroll: true });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Profil zdrowotny</h2>
            <p class="mt-1 text-sm text-gray-600">
                Wiek, wzrost, cele, kontuzje i preferencje żywieniowe — kontekst dla trenera AI.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <InputLabel for="age" value="Wiek" />
                <TextInput id="age" type="number" class="mt-1 block w-full" v-model="form.age" />
                <InputError class="mt-2" :message="form.errors.age" />
            </div>

            <div>
                <InputLabel for="height_cm" value="Wzrost (cm)" />
                <TextInput id="height_cm" type="number" class="mt-1 block w-full" v-model="form.height_cm" />
                <InputError class="mt-2" :message="form.errors.height_cm" />
            </div>

            <div>
                <InputLabel for="weight_goal_kg" value="Cel wagowy (kg)" />
                <TextInput id="weight_goal_kg" type="number" step="0.1" class="mt-1 block w-full" v-model="form.weight_goal_kg" />
                <InputError class="mt-2" :message="form.errors.weight_goal_kg" />
            </div>

            <div>
                <InputLabel for="injuries" value="Kontuzje / ograniczenia" />
                <textarea id="injuries" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" v-model="form.injuries" rows="3"></textarea>
                <InputError class="mt-2" :message="form.errors.injuries" />
            </div>

            <div>
                <InputLabel for="dietary_preferences" value="Preferencje żywieniowe / wykluczenia" />
                <textarea id="dietary_preferences" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" v-model="form.dietary_preferences" rows="3"></textarea>
                <InputError class="mt-2" :message="form.errors.dietary_preferences" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Zapisz</PrimaryButton>
                <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Zapisano.</p>
            </div>
        </form>
    </section>
</template>
