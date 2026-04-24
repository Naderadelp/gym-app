<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import api from '@/api/index.js';

const router = useRouter();
const weeklyVolume = ref(0);
const latestSession = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const [volRes, sessRes] = await Promise.all([
            api.get('/v1/analytics/volume?weeks=1'),
            api.get('/v1/workout-sessions?per_page=1&sort=-started_at'),
        ]);
        const rows = volRes.data?.data ?? [];
        weeklyVolume.value = rows[rows.length - 1]?.total_volume ?? 0;
        latestSession.value = sessRes.data?.data?.[0] ?? null;
    } catch {}
    loading.value = false;
});

async function startWorkout(routineId = null) {
    const { data } = await api.post('/v1/workout-sessions', { routine_id: routineId });
    router.push(`/workouts/${data.data.id}`);
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <button @click="startWorkout()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Start Empty Workout
                </button>
            </div>

            <div v-if="loading" class="text-gray-400 text-sm">Loading…</div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500 font-medium">Weekly Volume</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-1">
                        {{ weeklyVolume > 0 ? weeklyVolume.toLocaleString() + ' kg' : '—' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Total weight × reps this week</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500 font-medium">Last Workout</p>
                    <template v-if="latestSession">
                        <p class="text-xl font-bold text-gray-800 mt-1">
                            {{ new Date(latestSession.started_at).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
                        </p>
                    </template>
                    <template v-else>
                        <p class="text-gray-400 mt-2 text-sm">No workouts yet — start one!</p>
                    </template>
                </div>
            </div>

            <div v-if="!loading && !latestSession" class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 text-center">
                <p class="text-indigo-800 font-medium">Welcome! Ready for your first workout?</p>
                <p class="text-indigo-600 text-sm mt-1">Start a session or <RouterLink to="/routines" class="underline">browse routines</RouterLink>.</p>
            </div>
        </div>
    </AppLayout>
</template>
