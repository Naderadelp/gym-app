<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import api from '@/api/index.js';

const router = useRouter();
const route = useRoute();
const routineId = route.params.id;

const name = ref(''); const description = ref('');
const availableExercises = ref([]); const selectedExercises = ref([]);
const searchQuery = ref(''); const errors = ref({});

const filteredExercises = computed(() =>
    availableExercises.value.filter(e =>
        e.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        e.primary_muscle?.toLowerCase().includes(searchQuery.value.toLowerCase())
    ).slice(0, 10)
);

function addExercise(ex) {
    if (selectedExercises.value.some(e => e.exercise_id === ex.id)) return;
    selectedExercises.value.push({ exercise_id: ex.id, name: ex.name, primary_muscle: ex.primary_muscle, order: selectedExercises.value.length + 1, target_sets: 3, target_reps: 10, target_rest_seconds: 60 });
    searchQuery.value = '';
}

function removeExercise(idx) {
    selectedExercises.value.splice(idx, 1);
    selectedExercises.value.forEach((e, i) => { e.order = i + 1; });
}

async function submit() {
    errors.value = {};
    const payload = {
        name: name.value, description: description.value,
        exercises: selectedExercises.value.map((e, i) => ({ exercise_id: e.exercise_id, order: i + 1, target_sets: Number(e.target_sets), target_reps: e.target_reps ? Number(e.target_reps) : null, target_rest_seconds: e.target_rest_seconds ? Number(e.target_rest_seconds) : null })),
    };
    try {
        if (routineId) await api.put(`/v1/routines/${routineId}`, payload);
        else await api.post('/v1/routines', payload);
        router.push('/routines');
    } catch (e) {
        errors.value = e.response?.data?.errors ?? {};
    }
}

onMounted(async () => {
    const [exRes] = await Promise.all([api.get('/v1/exercises?per_page=500')]);
    availableExercises.value = exRes.data.data;
    if (routineId) {
        const { data } = await api.get(`/v1/routines/${routineId}`);
        name.value = data.data.name;
        description.value = data.data.description ?? '';
        selectedExercises.value = (data.data.exercises ?? []).map((re, idx) => ({
            exercise_id: re.exercise_id, name: re.exercise?.name ?? '', primary_muscle: re.exercise?.primary_muscle ?? '',
            order: re.order ?? idx + 1, target_sets: re.target_sets ?? 3, target_reps: re.target_reps ?? 10, target_rest_seconds: re.target_rest_seconds ?? 60,
        }));
    }
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-3xl">
            <div class="flex items-center gap-3">
                <RouterLink to="/routines" class="text-gray-400 hover:text-gray-600">← Back</RouterLink>
                <h1 class="text-2xl font-bold text-gray-900">{{ routineId ? 'Edit Routine' : 'New Routine' }}</h1>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Routine Name *</label>
                    <input v-model="name" type="text" placeholder="e.g. Push Day" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea v-model="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Add Exercises</h2>
                <input v-model="searchQuery" type="text" placeholder="Search exercises…" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-3" />
                <div v-if="searchQuery" class="border border-gray-200 rounded-lg max-h-48 overflow-y-auto divide-y">
                    <div v-if="filteredExercises.length === 0" class="px-3 py-3 text-sm text-gray-400">No results</div>
                    <button v-for="ex in filteredExercises" :key="ex.id" @click="addExercise(ex)" type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 flex justify-between">
                        <span>{{ ex.name }}</span><span class="text-xs text-gray-400">{{ ex.primary_muscle }}</span>
                    </button>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Selected Exercises ({{ selectedExercises.length }})</h2>
                <div v-if="selectedExercises.length === 0" class="text-sm text-gray-400 text-center py-6">Search above to add exercises</div>
                <div v-else class="space-y-3">
                    <div v-for="(ex, idx) in selectedExercises" :key="ex.exercise_id" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <span class="w-6 text-center text-sm text-gray-400 font-mono">{{ idx + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm text-gray-900 truncate">{{ ex.name }}</p>
                            <p class="text-xs text-gray-400">{{ ex.primary_muscle }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <label class="text-gray-500">Sets</label>
                            <input v-model="ex.target_sets" type="number" min="1" max="20" class="w-12 border border-gray-300 rounded px-1.5 py-1 text-center" />
                            <label class="text-gray-500">Reps</label>
                            <input v-model="ex.target_reps" type="number" min="1" max="100" class="w-12 border border-gray-300 rounded px-1.5 py-1 text-center" />
                        </div>
                        <button @click="removeExercise(idx)" type="button" class="text-red-400 hover:text-red-600 text-lg">✕</button>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <RouterLink to="/routines" class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-5 py-2 rounded-lg font-medium text-sm">Cancel</RouterLink>
                <button @click="submit" :disabled="!name || selectedExercises.length === 0" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-5 py-2 rounded-lg font-medium text-sm transition-colors">
                    {{ routineId ? 'Save Changes' : 'Create Routine' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
