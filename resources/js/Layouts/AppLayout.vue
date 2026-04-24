<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);

const navLinks = [
    { name: 'Dashboard', routeName: 'dashboard' },
    { name: 'Exercises', routeName: 'exercises.index' },
    { name: 'Routines', routeName: 'routines.index' },
    { name: 'Analytics', routeName: 'analytics.index' },
    { name: 'Profile', routeName: 'profile.edit' },
];

function isActive(routeName) {
    return page.url.startsWith(route(routeName).replace(window.location.origin, ''));
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-6">
                        <Link :href="route('dashboard')" class="text-indigo-600 font-bold text-lg">GymApp</Link>
                        <div class="hidden sm:flex gap-4">
                            <Link
                                v-for="link in navLinks"
                                :key="link.routeName"
                                :href="route(link.routeName)"
                                class="text-sm font-medium transition-colors"
                                :class="isActive(link.routeName)
                                    ? 'text-indigo-600 border-b-2 border-indigo-600 pb-0.5'
                                    : 'text-gray-600 hover:text-gray-900'"
                            >
                                {{ link.name }}
                            </Link>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span v-if="user" class="text-sm text-gray-500">{{ user.display_name || user.name }}</span>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="text-sm text-gray-500 hover:text-gray-900"
                        >Logout</Link>
                    </div>
                </div>
            </div>
        </nav>

        <div v-if="flash?.success" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
                {{ flash.success }}
            </div>
        </div>
        <div v-if="flash?.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                {{ flash.error }}
            </div>
        </div>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
</template>
