<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    muscleOptions: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    primary_muscle: '',
    sub_muscle_target: '',
    difficulty_level: 1,
    description: '',
    equipment_required: '',
    demonstration: null,
});

function submit() {
    form.post(route('exercises.store'), {
        forceFormData: true,
        onSuccess: () => emit('close'),
    });
}

function handleFile(event) {
    form.demonstration = event.target.files[0];
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b">
                <h2 class="text-lg font-semibold">Add Custom Exercise</h2>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary Muscle *</label>
                        <input v-model="form.primary_muscle" type="text" list="muscle-list"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <datalist id="muscle-list">
                            <option v-for="m in muscleOptions" :key="m" :value="m" />
                        </datalist>
                        <p v-if="form.errors.primary_muscle" class="text-red-500 text-xs mt-1">{{ form.errors.primary_muscle }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sub Muscle *</label>
                        <input v-model="form.sub_muscle_target" type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.sub_muscle_target" class="text-red-500 text-xs mt-1">{{ form.errors.sub_muscle_target }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty *</label>
                        <select v-model="form.difficulty_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option :value="1">1 — Beginner</option>
                            <option :value="2">2 — Intermediate</option>
                            <option :value="3">3 — Advanced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Equipment</label>
                        <input v-model="form.equipment_required" type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Barbell" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea v-model="form.description" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Demonstration Image</label>
                    <input type="file" accept="image/*" @change="handleFile" class="text-sm text-gray-600" />
                    <p v-if="form.errors.demonstration" class="text-red-500 text-xs mt-1">{{ form.errors.demonstration }}</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="emit('close')"
                        class="flex-1 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        {{ form.processing ? 'Creating…' : 'Create Exercise' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
