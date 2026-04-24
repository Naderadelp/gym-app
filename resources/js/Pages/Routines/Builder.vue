<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    availableExercises: Array,
    routine: Object,
});

const searchQuery = ref('');

const filteredExercises = computed(() =>
    props.availableExercises.filter(e =>
        e.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        e.primary_muscle?.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
);

const selectedExercises = ref(
    props.routine?.routine_exercises?.map((re, idx) => ({
        exercise_id:         re.exercise_id,
        name:                re.exercise?.name ?? '',
        primary_muscle:      re.exercise?.primary_muscle ?? '',
        order:               re.order ?? idx + 1,
        target_sets:         re.target_sets ?? 3,
        target_reps:         re.target_reps ?? 10,
        target_rest_seconds: re.target_rest_seconds ?? 60,
    })) ?? []
);

const form = useForm({
    name:        props.routine?.name ?? '',
    description: props.routine?.description ?? '',
    exercises:   selectedExercises,
});

function addExercise(exercise) {
    if (selectedExercises.value.some(e => e.exercise_id === exercise.id)) return;
    selectedExercises.value.push({
        exercise_id:         exercise.id,
        name:                exercise.name,
        primary_muscle:      exercise.primary_muscle,
        order:               selectedExercises.value.length + 1,
        target_sets:         3,
        target_reps:         10,
        target_rest_seconds: 60,
    });
    searchQuery.value = '';
}

function removeExercise(index) {
    selectedExercises.value.splice(index, 1);
    selectedExercises.value.forEach((e, i) => { e.order = i + 1; });
}

function submit() {
    const payload = {
        name:        form.name,
        description: form.description,
        exercises:   selectedExercises.value.map((e, i) => ({
            exercise_id:         e.exercise_id,
            order:               i + 1,
            target_sets:         Number(e.target_sets),
            target_reps:         e.target_reps ? Number(e.target_reps) : null,
            target_rest_seconds: e.target_rest_seconds ? Number(e.target_rest_seconds) : null,
        })),
    };

    if (props.routine) {
        useForm(payload).put(route('routines.update', props.routine.id));
    } else {
        useForm(payload).post(route('routines.store'));
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-3xl">
            <div class="flex items-center gap-3">
                <Link :href="route('routines.index')" class="text-gray-400 hover:text-gray-600">← Back</Link>
                <h1 class="text-2xl font-bold text-gray-900">{{ routine ? 'Edit Routine' : 'New Routine' }}</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Routine Name *</label>
                    <input v-model="form.name" type="text" placeholder="e.g. Push Day"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea v-model="form.description" rows="2" placeholder="Optional notes…"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                </div>
            </div>

            <!-- Exercise Search -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Add Exercises</h2>
                <input v-model="searchQuery" type="text" placeholder="Search exercises by name or muscle…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-3" />

                <div v-if="searchQuery" class="border border-gray-200 rounded-lg max-h-48 overflow-y-auto divide-y">
                    <div v-if="filteredExercises.length === 0" class="px-3 py-3 text-sm text-gray-400">No results</div>
                    <button v-for="ex in filteredExercises.slice(0, 10)" :key="ex.id"
                        @click="addExercise(ex)" type="button"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 flex items-center justify-between">
                        <span>{{ ex.name }}</span>
                        <span class="text-xs text-gray-400">{{ ex.primary_muscle }}</span>
                    </button>
                </div>
            </div>

            <!-- Selected Exercises -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-3">
                    Selected Exercises ({{ selectedExercises.length }})
                </h2>

                <div v-if="selectedExercises.length === 0" class="text-sm text-gray-400 text-center py-6">
                    Search above to add exercises
                </div>

                <div v-else class="space-y-3">
                    <div v-for="(ex, idx) in selectedExercises" :key="ex.exercise_id"
                        class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <span class="w-6 text-center text-sm text-gray-400 font-mono">{{ idx + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm text-gray-900 truncate">{{ ex.name }}</p>
                            <p class="text-xs text-gray-400">{{ ex.primary_muscle }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <label class="text-gray-500">Sets</label>
                            <input v-model="ex.target_sets" type="number" min="1" max="20"
                                class="w-12 border border-gray-300 rounded px-1.5 py-1 text-center" />
                            <label class="text-gray-500">Reps</label>
                            <input v-model="ex.target_reps" type="number" min="1" max="100"
                                class="w-12 border border-gray-300 rounded px-1.5 py-1 text-center" placeholder="—" />
                            <label class="text-gray-500">Rest(s)</label>
                            <input v-model="ex.target_rest_seconds" type="number" min="0" max="600"
                                class="w-14 border border-gray-300 rounded px-1.5 py-1 text-center" />
                        </div>
                        <button @click="removeExercise(idx)" type="button"
                            class="text-red-400 hover:text-red-600 text-lg leading-none ml-1">✕</button>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <Link :href="route('routines.index')"
                    class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-5 py-2 rounded-lg font-medium text-sm transition-colors">
                    Cancel
                </Link>
                <button @click="submit" :disabled="!form.name || selectedExercises.length === 0"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-5 py-2 rounded-lg font-medium text-sm transition-colors">
                    {{ routine ? 'Save Changes' : 'Create Routine' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
