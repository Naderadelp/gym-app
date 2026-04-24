<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    latestSession: Object,
    weeklyVolume: Number,
});

const startForm = useForm({});

function startWorkout() {
    startForm.post(route('workouts.start'));
}

const lastWorkoutDate = computed(() => {
    if (!props.latestSession) return null;
    return new Date(props.latestSession.started_at).toLocaleDateString('en-US', {
        weekday: 'short', month: 'short', day: 'numeric',
    });
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <button
                    @click="startWorkout"
                    :disabled="startForm.processing"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                >
                    Start Empty Workout
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500 font-medium">Weekly Volume</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-1">
                        {{ weeklyVolume > 0 ? weeklyVolume.toLocaleString() + ' kg' : '—' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Total weight × reps this week (excl. warmups)</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500 font-medium">Last Workout</p>
                    <template v-if="latestSession">
                        <p class="text-xl font-bold text-gray-800 mt-1">{{ lastWorkoutDate }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ latestSession.routine || 'Empty session' }}</p>
                    </template>
                    <template v-else>
                        <p class="text-gray-400 mt-2 text-sm">No workouts yet</p>
                        <p class="text-xs text-gray-400 mt-1">Hit "Start Empty Workout" to begin!</p>
                    </template>
                </div>
            </div>

            <div v-if="!latestSession" class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 text-center">
                <p class="text-indigo-800 font-medium">Welcome! Ready for your first workout?</p>
                <p class="text-indigo-600 text-sm mt-1">
                    Start an empty session or
                    <Link :href="route('routines.index')" class="underline">browse your routines</Link>.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
