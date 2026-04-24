<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    bodyMetrics: Array,
});

const profileForm = useForm({
    name: props.user.name,
    display_name: props.user.display_name ?? '',
    unit_preference: props.user.unit_preference ?? 'metric',
});

const avatarForm = useForm({ avatar: null });

const metricForm = useForm({
    logged_at: new Date().toISOString().slice(0, 10),
    weight: '',
});

function submitProfile() {
    profileForm.put(route('profile.update'));
}

function submitAvatar(event) {
    avatarForm.avatar = event.target.files[0];
    avatarForm.post(route('profile.avatar'), {
        forceFormData: true,
        onSuccess: () => { avatarForm.reset(); event.target.value = ''; },
    });
}

function submitMetric() {
    metricForm.post(route('body-metrics.store'), {
        onSuccess: () => metricForm.reset('weight'),
    });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8 max-w-2xl">
            <h1 class="text-2xl font-bold text-gray-900">Profile</h1>

            <!-- Avatar -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Avatar</h2>
                <div class="flex items-center gap-4">
                    <img
                        v-if="user.avatar_url"
                        :src="user.avatar_url"
                        class="w-16 h-16 rounded-full object-cover"
                        alt="Avatar"
                    />
                    <div v-else class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl">
                        {{ user.name[0].toUpperCase() }}
                    </div>
                    <div>
                        <input type="file" accept="image/*" @change="submitAvatar" class="text-sm text-gray-600" />
                        <p v-if="avatarForm.errors.avatar" class="text-red-500 text-xs mt-1">{{ avatarForm.errors.avatar }}</p>
                    </div>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Profile Info</h2>
                <form @submit.prevent="submitProfile" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input v-model="profileForm.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="profileForm.errors.name" class="text-red-500 text-xs mt-1">{{ profileForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                        <input v-model="profileForm.display_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Preference</label>
                        <select v-model="profileForm.unit_preference" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="metric">Metric (kg / cm)</option>
                            <option value="imperial">Imperial (lbs / in)</option>
                        </select>
                    </div>
                    <button type="submit" :disabled="profileForm.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Save Changes
                    </button>
                </form>
            </div>

            <!-- Body Metrics -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Body Metrics</h2>
                <form @submit.prevent="submitMetric" class="flex gap-3 items-end mb-6">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                        <input v-model="metricForm.logged_at" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Weight ({{ user.unit_preference === 'imperial' ? 'lbs' : 'kg' }})</label>
                        <input v-model="metricForm.weight" type="number" step="0.1" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-24" placeholder="75.0" />
                    </div>
                    <button type="submit" :disabled="metricForm.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Log
                    </button>
                </form>

                <div v-if="bodyMetrics.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="pb-2">Date</th>
                                <th class="pb-2">Weight</th>
                                <th class="pb-2">Height</th>
                                <th class="pb-2">Body Fat %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in bodyMetrics" :key="m.id" class="border-b last:border-0">
                                <td class="py-2">{{ m.logged_at }}</td>
                                <td class="py-2">{{ m.weight ?? '—' }}</td>
                                <td class="py-2">{{ m.height ?? '—' }}</td>
                                <td class="py-2">{{ m.body_fat_percentage ? m.body_fat_percentage + '%' : '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-gray-400 text-sm">No metrics logged yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
