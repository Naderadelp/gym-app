<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.js';

const router = useRouter();
const auth = useAuthStore();
const email = ref(''); const password = ref(''); const error = ref(''); const loading = ref(false);

async function submit() {
    error.value = ''; loading.value = true;
    try {
        await auth.login(email.value, password.value);
        router.push('/dashboard');
    } catch (e) {
        error.value = e.response?.data?.message || 'Invalid credentials.';
    } finally { loading.value = false; }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-sm p-8 w-full max-w-sm space-y-5">
            <h1 class="text-2xl font-bold text-gray-900 text-center">GymApp</h1>
            <p v-if="error" class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-3 py-2">{{ error }}</p>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input v-model="password" type="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <button type="submit" :disabled="loading" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white py-2 rounded-lg font-medium text-sm transition-colors">
                    {{ loading ? 'Signing in…' : 'Sign In' }}
                </button>
            </form>
            <p class="text-center text-sm text-gray-500">No account? <RouterLink to="/register" class="text-indigo-600 hover:underline">Register</RouterLink></p>
        </div>
    </div>
</template>
