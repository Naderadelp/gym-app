<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, Link } from '@inertiajs/vue3';
import SmartGeneratorModal from './SmartGeneratorModal.vue';

defineProps({
    routines: Array,
});

const showGenerator = ref(false);

function startWorkout(routineId) {
    router.post(route('workouts.start'), { routine_id: routineId });
}

function deleteRoutine(routineId) {
    if (confirm('Delete this routine?')) {
        router.delete(route('routines.destroy', routineId));
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Routines</h1>
                <div class="flex gap-3">
                    <button @click="showGenerator = true"
                        class="border border-indigo-300 text-indigo-600 hover:bg-indigo-50 px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                        ✨ Generate Smart Workout
                    </button>
                    <Link :href="route('routines.create')"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                        + Create Routine
                    </Link>
                </div>
            </div>

            <div v-if="routines.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="routine in routines" :key="routine.id"
                    class="bg-white rounded-xl shadow-sm p-5 flex flex-col gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ routine.name }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ routine.routine_exercises_count }} exercise{{ routine.routine_exercises_count !== 1 ? 's' : '' }}
                        </p>
                        <p v-if="routine.description" class="text-sm text-gray-400 mt-1 truncate">{{ routine.description }}</p>
                    </div>
                    <div class="flex gap-2 mt-auto">
                        <button @click="startWorkout(routine.id)"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                            ▶ Start
                        </button>
                        <Link :href="route('routines.show', routine.id)"
                            class="flex-1 text-center border border-gray-300 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                            Edit
                        </Link>
                        <button @click="deleteRoutine(routine.id)"
                            class="border border-red-200 text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg text-sm transition-colors">
                            ✕
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
                <p class="text-lg font-medium">No routines yet</p>
                <p class="text-sm mt-1">Create a routine or generate one automatically.</p>
                <div class="flex justify-center gap-3 mt-4">
                    <Link :href="route('routines.create')"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Create Routine
                    </Link>
                    <button @click="showGenerator = true"
                        class="border border-indigo-300 text-indigo-600 hover:bg-indigo-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Generate Smart Workout
                    </button>
                </div>
            </div>
        </div>

        <SmartGeneratorModal v-if="showGenerator" @close="showGenerator = false" />
    </AppLayout>
</template>
