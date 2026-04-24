<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import CreateModal from './CreateModal.vue';

const props = defineProps({
    exercises: Object,
    muscleOptions: Array,
    equipmentOptions: Array,
    filters: Object,
});

const showModal = ref(false);
const selectedMuscle = ref(props.filters?.filter?.primary_muscle ?? '');
const selectedEquipment = ref(props.filters?.filter?.equipment_required ?? '');

function applyFilters() {
    const filter = {};
    if (selectedMuscle.value) filter.primary_muscle = selectedMuscle.value;
    if (selectedEquipment.value) filter.equipment_required = selectedEquipment.value;

    router.get(route('exercises.index'), { filter }, { preserveState: true, replace: true });
}

const difficultyLabel = (level) => ['', 'Beginner', 'Intermediate', 'Advanced'][level] ?? level;
const difficultyColor = (level) => ['', 'bg-green-100 text-green-700', 'bg-yellow-100 text-yellow-700', 'bg-red-100 text-red-700'][level] ?? '';
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <h1 class="text-2xl font-bold text-gray-900">Exercise Library</h1>
                <button @click="showModal = true"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                    + Add Exercise
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select v-model="selectedMuscle" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">All muscles</option>
                    <option v-for="m in muscleOptions" :key="m" :value="m">{{ m }}</option>
                </select>
                <select v-model="selectedEquipment" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">All equipment</option>
                    <option v-for="e in equipmentOptions" :key="e" :value="e">{{ e }}</option>
                </select>
            </div>

            <!-- Exercise Grid -->
            <div v-if="exercises.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="exercise in exercises.data" :key="exercise.id"
                    class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div v-if="exercise.demonstration_url" class="h-36 bg-gray-100">
                        <img :src="exercise.demonstration_url" :alt="exercise.name" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="h-36 bg-gradient-to-br from-indigo-50 to-indigo-100 flex items-center justify-center">
                        <span class="text-4xl">💪</span>
                    </div>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-gray-900">{{ exercise.name }}</p>
                            <span v-if="exercise.is_custom" class="text-xs bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded">Custom</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ exercise.primary_muscle }}</span>
                            <span :class="difficultyColor(exercise.difficulty_level)"
                                class="text-xs px-2 py-0.5 rounded">
                                {{ difficultyLabel(exercise.difficulty_level) }}
                            </span>
                            <span v-if="exercise.equipment_required" class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded">
                                {{ exercise.equipment_required }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
                <p class="text-lg">No exercises found.</p>
                <p class="text-sm mt-1">Try clearing filters or add a custom exercise.</p>
            </div>

            <!-- Pagination -->
            <div v-if="exercises.last_page > 1" class="flex justify-center gap-2">
                <component
                    v-for="link in exercises.links"
                    :key="link.label"
                    :is="link.url ? 'a' : 'span'"
                    :href="link.url || undefined"
                    v-html="link.label"
                    class="px-3 py-1.5 rounded text-sm border"
                    :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                />
            </div>
        </div>

        <CreateModal v-if="showModal" @close="showModal = false" :muscle-options="muscleOptions" />
    </AppLayout>
</template>
