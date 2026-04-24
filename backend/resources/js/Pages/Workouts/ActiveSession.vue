<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import WorkoutTimer from './components/WorkoutTimer.vue';
import RestTimer from './components/RestTimer.vue';

const props = defineProps({
    session:        Object,
    routine:        Object,
    allLogs:        Array,
    logsByExercise: Object,
});

const logs = ref([...props.allLogs]);

const exercises = computed(() => {
    if (props.routine?.exercises?.length) {
        return props.routine.exercises;
    }
    const seen = new Set();
    const result = [];
    for (const log of logs.value) {
        if (!seen.has(log.exercise_id)) {
            seen.add(log.exercise_id);
            result.push({ exercise_id: log.exercise_id, name: log.exercise_name, primary_muscle: '' });
        }
    }
    return result;
});

function logsFor(exerciseId) {
    return logs.value.filter(l => l.exercise_id === exerciseId).sort((a, b) => a.set_number - b.set_number);
}

function nextSetNumber(exerciseId) {
    const existing = logsFor(exerciseId);
    return existing.length ? Math.max(...existing.map(l => l.set_number)) + 1 : 1;
}

const pendingSets = ref({});

function getPending(exerciseId) {
    if (!pendingSets.value[exerciseId]) {
        pendingSets.value[exerciseId] = {
            weight:   '',
            reps:     '',
            set_type: 'normal',
        };
    }
    return pendingSets.value[exerciseId];
}

const logging = ref({});

function logSet(exerciseId) {
    const pending = getPending(exerciseId);
    logging.value[exerciseId] = true;

    router.post(
        route('workout-logs.store.web', props.session.id),
        {
            exercise_id: exerciseId,
            set_number:  nextSetNumber(exerciseId),
            weight:      pending.weight || null,
            reps:        pending.reps || null,
            set_type:    pending.set_type,
        },
        {
            preserveScroll: true,
            only: ['allLogs'],
            onSuccess: (page) => {
                logs.value = page.props.allLogs ?? logs.value;
                pendingSets.value[exerciseId] = { weight: '', reps: '', set_type: 'normal' };
            },
            onFinish: () => { logging.value[exerciseId] = false; },
        }
    );
}

function removeLog(logId) {
    router.delete(route('workout-logs.destroy.web', logId), {
        preserveScroll: true,
        only: ['allLogs'],
        onSuccess: (page) => {
            logs.value = page.props.allLogs ?? logs.value.filter(l => l.id !== logId);
        },
    });
}

const finishing = ref(false);

function finish() {
    if (!confirm('Finish this workout?')) return;
    finishing.value = true;
    router.post(route('workouts.finish', props.session.id), {}, {
        onFinish: () => { finishing.value = false; },
    });
}

const addExerciseQuery = ref('');
const addingCustom = ref(false);
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-2xl">
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-sm p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">{{ routine?.name ?? 'Free Workout' }}</p>
                    <WorkoutTimer :started-at="session.started_at" />
                </div>
                <button @click="finish" :disabled="finishing"
                    class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    {{ finishing ? 'Saving…' : 'Finish Workout' }}
                </button>
            </div>

            <!-- Exercise Blocks -->
            <div v-for="ex in exercises" :key="ex.exercise_id"
                class="bg-white rounded-xl shadow-sm p-5 space-y-3">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ ex.name }}</h3>
                        <p v-if="ex.primary_muscle" class="text-xs text-gray-400">{{ ex.primary_muscle }}</p>
                    </div>
                    <div v-if="ex.target_sets" class="text-xs text-gray-400 text-right">
                        {{ ex.target_sets }} sets × {{ ex.target_reps ?? '—' }} reps
                    </div>
                </div>

                <!-- Logged Sets -->
                <div v-if="logsFor(ex.exercise_id).length > 0" class="divide-y divide-gray-100">
                    <div v-for="log in logsFor(ex.exercise_id)" :key="log.id"
                        class="flex items-center gap-3 py-1.5 text-sm">
                        <span class="w-5 text-gray-400 font-mono text-xs">{{ log.set_number }}</span>
                        <span class="w-16 text-center font-medium">
                            {{ log.weight != null ? log.weight + ' kg' : '—' }}
                        </span>
                        <span class="w-16 text-center font-medium">
                            {{ log.reps != null ? log.reps + ' reps' : '—' }}
                        </span>
                        <span class="flex-1 text-xs text-gray-400 capitalize">{{ log.set_type }}</span>
                        <button @click="removeLog(log.id)" type="button"
                            class="text-red-300 hover:text-red-500 text-xs">✕</button>
                    </div>
                </div>

                <!-- Log New Set Row -->
                <div class="flex items-center gap-2 pt-1">
                    <span class="w-5 text-center text-xs text-gray-400 font-mono">
                        {{ nextSetNumber(ex.exercise_id) }}
                    </span>
                    <input v-model="getPending(ex.exercise_id).weight" type="number" min="0" step="0.5"
                        placeholder="kg"
                        class="w-16 border border-gray-300 rounded px-1.5 py-1 text-sm text-center" />
                    <input v-model="getPending(ex.exercise_id).reps" type="number" min="0"
                        placeholder="reps"
                        class="w-16 border border-gray-300 rounded px-1.5 py-1 text-sm text-center" />
                    <select v-model="getPending(ex.exercise_id).set_type"
                        class="border border-gray-300 rounded px-1.5 py-1 text-xs">
                        <option value="warmup">Warmup</option>
                        <option value="normal">Normal</option>
                        <option value="drop">Drop</option>
                        <option value="failure">Failure</option>
                    </select>
                    <button @click="logSet(ex.exercise_id)" :disabled="logging[ex.exercise_id]"
                        type="button"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                        {{ logging[ex.exercise_id] ? '…' : 'Log' }}
                    </button>
                    <RestTimer :default-seconds="ex.target_rest_seconds ?? 60" />
                </div>
            </div>

            <!-- Empty state when no routine -->
            <div v-if="exercises.length === 0"
                class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
                <p class="text-base font-medium">No exercises yet</p>
                <p class="text-sm mt-1">Log a set above to add exercises to this session.</p>
            </div>
        </div>
    </AppLayout>
</template>
