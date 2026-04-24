<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth.js';
import api from '@/api/index.js';

const auth = useAuthStore();
const form = ref({ name: '', display_name: '', unit_preference: 'metric' });
const metrics = ref([]); const newWeight = ref('');
const avatar = ref(null); const success = ref(''); const errors = ref({});

onMounted(async () => {
    const { data } = await api.get('/v1/profile');
    form.value = { name: data.data.name, display_name: data.data.display_name ?? '', unit_preference: data.data.unit_preference ?? 'metric' };
    const mRes = await api.get('/v1/body-metrics?sort=-logged_at&per_page=10');
    metrics.value = mRes.data.data;
});

async function updateProfile() {
    errors.value = {}; success.value = '';
    try {
        const { data } = await api.put('/v1/profile', form.value);
        auth.user = data.data;
        localStorage.setItem('user', JSON.stringify(data.data));
        success.value = 'Profile updated.';
    } catch (e) { errors.value = e.response?.data?.errors ?? {}; }
}

async function uploadAvatar() {
    if (!avatar.value) return;
    const fd = new FormData(); fd.append('avatar', avatar.value);
    await api.post('/v1/profile/avatar', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    success.value = 'Avatar updated.';
}

async function logWeight() {
    await api.post('/v1/body-metrics', { weight: Number(newWeight.value), logged_at: new Date().toISOString().slice(0,10) });
    newWeight.value = '';
    const mRes = await api.get('/v1/body-metrics?sort=-logged_at&per_page=10');
    metrics.value = mRes.data.data;
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-2xl">
            <h1 class="text-2xl font-bold text-gray-900">Profile</h1>
            <p v-if="success" class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ success }}</p>

            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Details</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                    <input v-model="form.display_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Units</label>
                    <select v-model="form.unit_preference" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="metric">Metric (kg)</option>
                        <option value="imperial">Imperial (lbs)</option>
                    </select>
                </div>
                <button @click="updateProfile" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-medium text-sm transition-colors">Save</button>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Avatar</h2>
                <input type="file" accept="image/*" @change="avatar = $event.target.files[0]" class="text-sm" />
                <button @click="uploadAvatar" :disabled="!avatar" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-5 py-2 rounded-lg font-medium text-sm transition-colors">Upload</button>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Body Weight</h2>
                <div class="flex gap-3">
                    <input v-model="newWeight" type="number" step="0.1" placeholder="kg" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32" />
                    <button @click="logWeight" :disabled="!newWeight" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Log Today</button>
                </div>
                <table v-if="metrics.length" class="w-full text-sm">
                    <thead><tr class="border-b border-gray-200"><th class="text-left py-2 font-medium text-gray-600">Date</th><th class="text-right py-2 font-medium text-gray-600">Weight</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="m in metrics" :key="m.id">
                            <td class="py-2 text-gray-700">{{ m.logged_at }}</td>
                            <td class="py-2 text-right font-semibold text-indigo-600">{{ m.weight }} kg</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
