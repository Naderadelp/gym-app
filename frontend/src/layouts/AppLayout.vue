<script setup>
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth.js';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const navLinks = [
    { name: 'Dashboard', path: '/dashboard' },
    { name: 'Exercises', path: '/exercises' },
    { name: 'Routines',  path: '/routines' },
    { name: 'Analytics', path: '/analytics' },
    { name: 'Profile',   path: '/profile' },
];

async function logout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-6">
                        <RouterLink to="/dashboard" class="text-indigo-600 font-bold text-lg">GymApp</RouterLink>
                        <div class="hidden sm:flex gap-4">
                            <RouterLink
                                v-for="link in navLinks"
                                :key="link.path"
                                :to="link.path"
                                class="text-sm font-medium transition-colors"
                                :class="route.path.startsWith(link.path)
                                    ? 'text-indigo-600 border-b-2 border-indigo-600 pb-0.5'
                                    : 'text-gray-600 hover:text-gray-900'"
                            >
                                {{ link.name }}
                            </RouterLink>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-500">{{ auth.user?.display_name || auth.user?.name }}</span>
                        <button @click="logout" class="text-sm text-gray-500 hover:text-gray-900">Logout</button>
                    </div>
                </div>
            </div>
        </nav>
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
</template>
