<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { localDate } from '@/localDateTime';

const props = defineProps({
    medications: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    dose: '',
    started_at: localDate(),
});

const submit = () => {
    form.post(route('medications.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const stop = (medication) => {
    useForm({ stopped_at: localDate() }).patch(
        route('medications.update', medication.id),
        { preserveScroll: true }
    );
};
</script>

<template>
    <Head title="Leki i suplementy" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leki i suplementy</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dodaj</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <InputLabel for="name" value="Nazwa" />
                            <TextInput id="name" class="mt-1 block w-full" v-model="form.name" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="dose" value="Dawka" />
                            <TextInput id="dose" class="mt-1 block w-full" v-model="form.dose" />
                            <InputError class="mt-2" :message="form.errors.dose" />
                        </div>
                        <div>
                            <InputLabel for="started_at" value="Od kiedy" />
                            <TextInput id="started_at" type="date" class="mt-1 block w-full" v-model="form.started_at" />
                            <InputError class="mt-2" :message="form.errors.started_at" />
                        </div>
                        <div class="sm:col-span-3">
                            <PrimaryButton :disabled="form.processing">Dodaj</PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg divide-y">
                    <div v-for="medication in medications" :key="medication.id" class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ medication.name }} — {{ medication.dose }}</p>
                            <p class="text-sm text-gray-500">
                                od {{ medication.started_at }}
                                <span v-if="medication.stopped_at"> · odstawiony {{ medication.stopped_at }}</span>
                                <span v-else class="text-green-700"> · aktywny</span>
                            </p>
                        </div>
                        <SecondaryButton v-if="!medication.stopped_at" @click="stop(medication)">Odstaw</SecondaryButton>
                    </div>
                    <p v-if="!medications.length" class="p-4 text-sm text-gray-600">Brak wpisów.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
