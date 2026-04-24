<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import api from '@/api/index.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const chartData = ref(null); const prs = ref([]);

const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() + ' kg' } } },
};

onMounted(async () => {
    const [volRes, prRes] = await Promise.all([
        api.get('/v1/analytics/volume'),
        api.get('/v1/analytics/personal-records'),
    ]);
    const rows = volRes.data?.data ?? [];
    chartData.value = {
        labels: rows.map(r => r.week_label ?? r.week),
        datasets: [{ label: 'Volume (kg)', data: rows.map(r => r.total_volume ?? 0), backgroundColor: 'rgba(99,102,241,0.7)', borderColor: 'rgba(99,102,241,1)', borderWidth: 1, borderRadius: 4 }],
    };
    prs.value = prRes.data?.data ?? [];
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <h1 class="text-2xl font-bold text-gray-900">Analytics</h1>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Weekly Volume — Last 12 Weeks</h2>
                <div class="h-64">
                    <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
                    <div v-else class="flex items-center justify-center h-full text-gray-400 text-sm">Loading…</div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Personal Records</h2>
                <div v-if="prs.length === 0" class="text-sm text-gray-400 text-center py-6">No records yet.</div>
                <table v-else class="w-full text-sm">
                    <thead><tr class="border-b border-gray-200">
                        <th class="text-left py-2 pr-4 font-medium text-gray-600">Exercise</th>
                        <th class="text-right py-2 pr-4 font-medium text-gray-600">Max Weight</th>
                        <th class="text-right py-2 font-medium text-gray-600">Best Reps</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="pr in prs" :key="pr.exercise_id" class="hover:bg-gray-50">
                            <td class="py-2 pr-4 font-medium text-gray-900">{{ pr.exercise_name ?? pr.exercise }}</td>
                            <td class="py-2 pr-4 text-right text-indigo-600 font-semibold">{{ pr.max_weight }} kg</td>
                            <td class="py-2 text-right text-gray-600">{{ pr.best_reps }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
