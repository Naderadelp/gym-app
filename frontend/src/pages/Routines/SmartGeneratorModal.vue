<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api/index.js';

const emit = defineEmits(['close']);
const router = useRouter();
const form = ref({ primary_muscle: '', difficulty_level: '' });
const error = ref(''); const loading = ref(false);

const MUSCLES = ['Chest','Back','Shoulders','Biceps','Triceps','Legs','Glutes','Core','Calves','Forearms'];

async function submit() {
    error.value = ''; loading.value = true;
    try {
        const { data } = await api.post('/v1/routines/generate', form.value);
        emit('close');
        router.push(`/routines/${data.data.id}/edit`);
    } catch (e) {
        error.value = e.response?.data?.message || 'No exercises found for that selection.';
    } finally { loading.value = false; }
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
                <p v-if="error" class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-3 py-2">{{ error }}</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Muscle *</label>
                    <select v-model="form.primary_muscle" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select a muscle group</option>
                        <option v-for="m in MUSCLES" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Difficulty *</label>
                    <select v-model="form.difficulty_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select difficulty</option>
                        <option :value="1">1 — Beginner</option>
                        <option :value="2">2 — Intermediate</option>
                        <option :value="3">3 — Advanced</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="emit('close')" class="flex-1 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</button>
                    <button type="submit" :disabled="loading || !form.primary_muscle || !form.difficulty_level" class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        {{ loading ? 'Generating…' : 'Generate' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
