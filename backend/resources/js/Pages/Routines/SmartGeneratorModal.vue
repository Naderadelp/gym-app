<script setup>
import { usePage, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const emit = defineEmits(['close']);

const page = usePage();
const flashError = computed(() => page.props.flash?.error);

const MUSCLE_OPTIONS = [
    'Chest', 'Back', 'Shoulders', 'Biceps', 'Triceps',
    'Legs', 'Glutes', 'Core', 'Calves', 'Forearms',
];

const form = useForm({
    primary_muscle: '',
    difficulty_level: '',
});

function submit() {
    form.post(route('routines.generate'), {
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
            <div class="flex items-center justify-between p-6 border-b">
                <h2 class="text-lg font-semibold">Generate Smart Workout</h2>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div v-if="flashError" class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-3 py-2">
                    {{ flashError }}
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Muscle *</label>
                    <select v-model="form.primary_muscle" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select a muscle group</option>
                        <option v-for="m in MUSCLE_OPTIONS" :key="m" :value="m">{{ m }}</option>
                    </select>
                    <p v-if="form.errors.primary_muscle" class="text-red-500 text-xs mt-1">{{ form.errors.primary_muscle }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Difficulty *</label>
                    <select v-model="form.difficulty_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select difficulty</option>
                        <option :value="1">1 — Beginner</option>
                        <option :value="2">2 — Intermediate</option>
                        <option :value="3">3 — Advanced</option>
                    </select>
                    <p v-if="form.errors.difficulty_level" class="text-red-500 text-xs mt-1">{{ form.errors.difficulty_level }}</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="emit('close')"
                        class="flex-1 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing || !form.primary_muscle || !form.difficulty_level"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        {{ form.processing ? 'Generating…' : 'Generate' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
