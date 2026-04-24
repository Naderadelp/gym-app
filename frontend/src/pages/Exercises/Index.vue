<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CreateModal from './CreateModal.vue';
import api from '@/api/index.js';

const exercises = ref([]); const pagination = ref(null);
const muscleOptions = ref([]); const equipmentOptions = ref([]);
const filters = ref({ primary_muscle: '', equipment_required: '' });
const showCreate = ref(false);

async function load(page = 1) {
    const params = { page, per_page: 20, ...Object.fromEntries(Object.entries(filters.value).filter(([,v]) => v)) };
    const { data } = await api.get('/v1/exercises', { params });
    exercises.value = data.data;
    pagination.value = data.meta;
    if (!muscleOptions.value.length) muscleOptions.value = data.muscle_options ?? [];
    if (!equipmentOptions.value.length) equipmentOptions.value = data.equipment_options ?? [];
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Exercises</h1>
                <button @click="showCreate = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                    + Add Exercise
                </button>
            </div>

            <div class="flex gap-3">
                <select v-model="filters.primary_muscle" @change="load()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Muscles</option>
                    <option v-for="m in muscleOptions" :key="m" :value="m">{{ m }}</option>
                </select>
                <select v-model="filters.equipment_required" @change="load()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Equipment</option>
                    <option v-for="e in equipmentOptions" :key="e" :value="e">{{ e }}</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="ex in exercises" :key="ex.id" class="bg-white rounded-xl shadow-sm p-4">
                    <img v-if="ex.demonstration_url" :src="ex.demonstration_url" class="w-full h-32 object-cover rounded-lg mb-3" />
                    <h3 class="font-semibold text-gray-900">{{ ex.name }}</h3>
                    <div class="flex gap-2 mt-1">
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">{{ ex.primary_muscle }}</span>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ ['', 'Beginner', 'Intermediate', 'Advanced'][ex.difficulty_level] }}</span>
                    </div>
                    <p v-if="ex.equipment_required" class="text-xs text-gray-400 mt-1">{{ ex.equipment_required }}</p>
                </div>
            </div>

            <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2">
                <button v-for="p in pagination.last_page" :key="p" @click="load(p)"
                    :class="p === pagination.current_page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    {{ p }}
                </button>
            </div>
        </div>

        <CreateModal v-if="showCreate" :muscle-options="muscleOptions" @close="showCreate = false; load()" />
    </AppLayout>
</template>
