<script setup>
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

defineProps({
    connected: { type: Boolean, required: true },
});

const connect = () => {
    window.location.href = route('strava.connect');
};

const disconnect = () => {
    if (confirm('Rozłączyć konto Strava? Zaimportowane dotychczas treningi zostaną w historii.')) {
        router.delete(route('strava.disconnect'));
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Strava</h2>
            <p class="mt-1 text-sm text-gray-600">
                Połącz konto Strava, aby importować biegi i sporty jednym kliknięciem.
                Bez połączenia zawsze dostępny jest ręczny wpis treningu.
            </p>
        </header>

        <div class="mt-4">
            <p v-if="connected" class="text-sm text-green-700 mb-3">Konto Strava połączone.</p>
            <p v-else class="text-sm text-gray-600 mb-3">Konto Strava nie jest połączone.</p>

            <PrimaryButton v-if="!connected" @click="connect">Połącz ze Strava</PrimaryButton>
            <DangerButton v-else @click="disconnect">Rozłącz Stravę</DangerButton>
        </div>
    </section>
</template>
