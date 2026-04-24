<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import WorkoutTimer from './components/WorkoutTimer.vue';
import RestTimer from './components/RestTimer.vue';
import api from '@/api/index.js';

const router = useRouter();
const route = useRoute();
const sessionId = route.params.id;

const session = ref(null); const logs = ref([]); const routine = ref(null);
const pending = ref({}); const finishing = ref(false);

const exercises = computed(() => {
    if (routine.value?.exercises?.length) return routine.value.exercises;
    const seen = new Set(); const result = [];
    for (const l of logs.value) {
        if (!seen.has(l.exercise_id)) { seen.add(l.exercise_id); result.push({ exercise_id: l.exercise_id, name: l.exercise?.name ?? '' }); }
    }
    return result;
});

function logsFor(exerciseId) { return logs.value.filter(l => l.exercise_id === exerciseId).sort((a,b) => a.set_number - b.set_number); }
function nextSet(exerciseId) { const ex = logsFor(exerciseId); return ex.length ? Math.max(...ex.map(l => l.set_number)) + 1 : 1; }
function getPending(exerciseId) { if (!pending.value[exerciseId]) pending.value[exerciseId] = { weight: '', reps: '', set_type: 'normal' }; return pending.value[exerciseId]; }

async function logSet(exerciseId) {
    const p = getPending(exerciseId);
    await api.post(`/v1/workout-sessions/${sessionId}/logs`, { exercise_id: exerciseId, set_number: nextSet(exerciseId), weight: p.weight || null, reps: p.reps || null, set_type: p.set_type });
    const { data } = await api.get(`/v1/workout-sessions/${sessionId}`);
    logs.value = data.data.workout_logs ?? [];
    pending.value[exerciseId] = { weight: '', reps: '', set_type: 'normal' };
}

async function removeLog(logId) {
    await api.delete(`/v1/workout-logs/${logId}`);
    logs.value = logs.value.filter(l => l.id !== logId);
}

async function finish() {
    if (!confirm('Finish this workout?')) return;
    finishing.value = true;
    await api.post(`/v1/workout-sessions/${sessionId}/finish`);
    router.push('/dashboard');
}

onMounted(async () => {
    const { data } = await api.get(`/v1/workout-sessions/${sessionId}`);
    session.value = data.data;
    logs.value = data.data.workout_logs ?? [];
    routine.value = data.data.routine ?? null;
});
</script>

<template>
    <AppLayout>
        <div v-if="!session" class="text-gray-400 text-sm p-8 text-center">Loading…</div>
        <div v-else class="space-y-6 max-w-2xl">
            <div class="bg-white rounded-xl shadow-sm p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">{{ routine?.name ?? 'Free Workout' }}</p>
                    <WorkoutTimer :started-at="session.started_at" />
                </div>
                <button @click="finish" :disabled="finishing" class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    {{ finishing ? 'Saving…' : 'Finish Workout' }}
                </button>
            </div>

            <div v-for="ex in exercises" :key="ex.exercise_id" class="bg-white rounded-xl shadow-sm p-5 space-y-3">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ ex.name }}</h3>
                        <p v-if="ex.primary_muscle" class="text-xs text-gray-400">{{ ex.primary_muscle }}</p>
                    </div>
                    <div v-if="ex.target_sets" class="text-xs text-gray-400">{{ ex.target_sets }} × {{ ex.target_reps ?? '—' }}</div>
                </div>
                <div v-if="logsFor(ex.exercise_id).length" class="divide-y divide-gray-100">
                    <div v-for="log in logsFor(ex.exercise_id)" :key="log.id" class="flex items-center gap-3 py-1.5 text-sm">
                        <span class="w-5 text-gray-400 font-mono text-xs">{{ log.set_number }}</span>
                        <span class="w-16 text-center font-medium">{{ log.weight != null ? log.weight + ' kg' : '—' }}</span>
                        <span class="w-16 text-center font-medium">{{ log.reps != null ? log.reps + ' reps' : '—' }}</span>
                        <span class="flex-1 text-xs text-gray-400 capitalize">{{ log.set_type }}</span>
                        <button @click="removeLog(log.id)" type="button" class="text-red-300 hover:text-red-500 text-xs">✕</button>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <span class="w-5 text-center text-xs text-gray-400 font-mono">{{ nextSet(ex.exercise_id) }}</span>
                    <input v-model="getPending(ex.exercise_id).weight" type="number" min="0" step="0.5" placeholder="kg" class="w-16 border border-gray-300 rounded px-1.5 py-1 text-sm text-center" />
                    <input v-model="getPending(ex.exercise_id).reps" type="number" min="0" placeholder="reps" class="w-16 border border-gray-300 rounded px-1.5 py-1 text-sm text-center" />
                    <select v-model="getPending(ex.exercise_id).set_type" class="border border-gray-300 rounded px-1.5 py-1 text-xs">
                        <option value="warmup">Warmup</option>
                        <option value="normal">Normal</option>
                        <option value="drop">Drop</option>
                        <option value="failure">Failure</option>
                    </select>
                    <button @click="logSet(ex.exercise_id)" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">Log</button>
                    <RestTimer :default-seconds="ex.target_rest_seconds ?? 60" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
