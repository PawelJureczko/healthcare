<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    reminders: { type: Array, default: () => [] },
});

const form = useForm({ type: '', interval_days: 90 });

const submit = () => {
    form.post(route('reminders.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const markDone = (reminder) => {
    router.patch(route('reminders.update', reminder.id), {}, { preserveScroll: true });
};

const statusLabel = (days) => {
    if (days === null) return 'nigdy nie wykonane';
    if (days < 0) return `zaległe o ${Math.abs(days)} dni`;
    if (days === 0) return 'termin dziś';
    return `za ${days} dni`;
};
</script>

<template>
    <Head title="Przypomnienia" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Przypomnienia o badaniach</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nowe przypomnienie</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <InputLabel for="type" value="Rodzaj badania" />
                            <TextInput id="type" class="mt-1 block w-full" v-model="form.type" placeholder="np. Lipidogram" />
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>
                        <div>
                            <InputLabel for="interval_days" value="Co ile dni" />
                            <TextInput id="interval_days" type="number" class="mt-1 block w-full" v-model="form.interval_days" />
                            <InputError class="mt-2" :message="form.errors.interval_days" />
                        </div>
                        <div>
                            <PrimaryButton :disabled="form.processing">Dodaj</PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg divide-y">
                    <div v-for="reminder in reminders" :key="reminder.id" class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ reminder.type }} — co {{ reminder.interval_days }} dni</p>
                            <p class="text-sm" :class="reminder.days_until_due !== null && reminder.days_until_due < 0 ? 'text-red-600' : 'text-gray-500'">
                                {{ statusLabel(reminder.days_until_due) }}
                            </p>
                        </div>
                        <SecondaryButton @click="markDone(reminder)">Wykonane dziś</SecondaryButton>
                    </div>
                    <p v-if="!reminders.length" class="p-4 text-sm text-gray-600">Brak przypomnień.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
