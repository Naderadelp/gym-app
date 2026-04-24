<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="w-full max-w-md space-y-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">GymApp</h1>
                <p class="mt-2 text-gray-600">Create your account</p>
            </div>

            <form @submit.prevent="submit" class="bg-white shadow-sm rounded-xl p-8 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :class="form.errors.name ? 'border-red-400' : 'border-gray-300'"
                    />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :class="form.errors.email ? 'border-red-400' : 'border-gray-300'"
                    />
                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input
                        v-model="form.password"
                        type="password"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2 rounded-lg transition-colors"
                >
                    {{ form.processing ? 'Creating account…' : 'Create account' }}
                </button>

                <p class="text-center text-sm text-gray-500">
                    Already have an account?
                    <Link :href="route('login')" class="text-indigo-600 hover:underline">Sign in</Link>
                </p>
            </form>
        </div>
    </div>
</template>
