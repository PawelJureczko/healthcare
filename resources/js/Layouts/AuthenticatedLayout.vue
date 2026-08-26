<script setup>
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const statusMessages = {
    'strava-connected': 'Konto Strava połączone.',
    'strava-connect-failed': 'Nie udało się połączyć konta Strava. Spróbuj ponownie.',
    'strava-disconnected': 'Konto Strava rozłączone.',
    'strava-not-connected': 'Najpierw połącz konto Strava w profilu.',
    'strava-sync-failed': 'Synchronizacja ze Stravą nie powiodła się. Spróbuj ponownie.',
    'run-saved': 'Bieg zapisany.',
    'goal-saved': 'Cel biegowy ustawiony.',
    'sport-session-saved': 'Sesja sportowa zapisana.',
};

const page = usePage();

const flashMessage = computed(() => {
    const status = page.props.flash?.status;

    if (!status) {
        return null;
    }

    if (status.startsWith('strava-synced:')) {
        const count = Number.parseInt(status.split(':')[1], 10) || 0;

        return `Zaimportowano ${count} nowych aktywności.`;
    }

    return statusMessages[status] ?? 'Zapisano.';
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav
                class="border-b border-gray-100 bg-white"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Panel
                                </NavLink>
                                <NavLink :href="route('body.index')" :active="route().current('body.index')">Ciało</NavLink>
                                <NavLink :href="route('blood-pressure.index')" :active="route().current('blood-pressure.index')">Ciśnienie</NavLink>
                                <NavLink :href="route('lab-results.index')" :active="route().current('lab-results.*')">Badania</NavLink>
                                <NavLink :href="route('medications.index')" :active="route().current('medications.index')">Leki</NavLink>
                                <NavLink :href="route('reminders.index')" :active="route().current('reminders.index')">Przypomnienia</NavLink>
                                <NavLink :href="route('runs.index')" :active="route().current('runs.index')">Bieganie</NavLink>
                                <NavLink :href="route('sport-sessions.index')" :active="route().current('sport-sessions.index')">Sporty</NavLink>
                                <NavLink :href="route('gym-workouts.index')" :active="route().current('gym-workouts.*')">Siłownia</NavLink>
                                <NavLink :href="route('exercises.index')" :active="route().current('exercises.*')">Ćwiczenia</NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profil
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Wyloguj
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Panel
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('body.index')" :active="route().current('body.index')">Ciało</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('blood-pressure.index')" :active="route().current('blood-pressure.index')">Ciśnienie</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('lab-results.index')" :active="route().current('lab-results.*')">Badania</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('medications.index')" :active="route().current('medications.index')">Leki</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('reminders.index')" :active="route().current('reminders.index')">Przypomnienia</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('runs.index')" :active="route().current('runs.index')">Bieganie</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('sport-sessions.index')" :active="route().current('sport-sessions.index')">Sporty</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('gym-workouts.index')" :active="route().current('gym-workouts.*')">Siłownia</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('exercises.index')" :active="route().current('exercises.*')">Ćwiczenia</ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                    >
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gray-800"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Wyloguj
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Flash Status Banner -->
            <div
                v-if="flashMessage"
                class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8"
            >
                <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    {{ flashMessage }}
                </div>
            </div>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
