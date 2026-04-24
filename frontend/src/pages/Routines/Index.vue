<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import SmartGeneratorModal from './SmartGeneratorModal.vue';
import api from '@/api/index.js';

const router = useRouter();
const routines = ref([]); const showGenerator = ref(false);

async function load() {
    const { data } = await api.get('/v1/routines');
    routines.value = data.data;
}

async function startWorkout(routineId) {
    const { data } = await api.post('/v1/workout-sessions', { routine_id: routineId });
    router.push(`/workouts/${data.data.id}`);
}

async function deleteRoutine(id) {
    if (!confirm('Delete this routine?')) return;
    await api.delete(`/v1/routines/${id}`);
    load();
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Routines</h1>
                <div class="flex gap-3">
                    <button @click="showGenerator = true" class="border border-indigo-300 text-indigo-600 hover:bg-indigo-50 px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                        ✨ Generate Smart Workout
                    </button>
                    <RouterLink to="/routines/create" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                        + Create Routine
                    </RouterLink>
                </div>
            </div>

            <div v-if="routines.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="r in routines" :key="r.id" class="bg-white rounded-xl shadow-sm p-5 flex flex-col gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ r.name }}</h3>
                        <p v-if="r.description" class="text-sm text-gray-400 mt-1 truncate">{{ r.description }}</p>
                    </div>
                    <div class="flex gap-2 mt-auto">
                        <button @click="startWorkout(r.id)" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">▶ Start</button>
                        <RouterLink :to="`/routines/${r.id}/edit`" class="flex-1 text-center border border-gray-300 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">Edit</RouterLink>
                        <button @click="deleteRoutine(r.id)" class="border border-red-200 text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg text-sm transition-colors">✕</button>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
                <p class="text-lg font-medium">No routines yet</p>
                <div class="flex justify-center gap-3 mt-4">
                    <RouterLink to="/routines/create" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Create Routine</RouterLink>
                    <button @click="showGenerator = true" class="border border-indigo-300 text-indigo-600 hover:bg-indigo-50 px-4 py-2 rounded-lg text-sm font-medium">Generate Smart Workout</button>
                </div>
            </div>
        </div>
        <SmartGeneratorModal v-if="showGenerator" @close="showGenerator = false; load()" />
    </AppLayout>
</template>
