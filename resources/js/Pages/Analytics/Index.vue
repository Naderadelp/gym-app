<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    weekLabels:      Array,
    volumeData:      Array,
    personalRecords: Array,
});

const chartData = {
    labels: props.weekLabels,
    datasets: [
        {
            label: 'Weekly Volume (kg)',
            data: props.volumeData,
            backgroundColor: 'rgba(99, 102, 241, 0.7)',
            borderColor: 'rgba(99, 102, 241, 1)',
            borderWidth: 1,
            borderRadius: 4,
        },
    ],
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        title: { display: false },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { callback: (v) => v.toLocaleString() + ' kg' },
        },
    },
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <h1 class="text-2xl font-bold text-gray-900">Analytics</h1>

            <!-- Volume Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Weekly Volume — Last 12 Weeks</h2>
                <div class="h-64">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Personal Records Table -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Personal Records</h2>

                <div v-if="personalRecords.length === 0" class="text-sm text-gray-400 text-center py-6">
                    No records yet — finish a workout to see your PRs.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 pr-4 font-medium text-gray-600">Exercise</th>
                                <th class="text-right py-2 pr-4 font-medium text-gray-600">Max Weight</th>
                                <th class="text-right py-2 font-medium text-gray-600">Best Reps</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="pr in personalRecords" :key="pr.exercise"
                                class="hover:bg-gray-50">
                                <td class="py-2 pr-4 font-medium text-gray-900">{{ pr.exercise }}</td>
                                <td class="py-2 pr-4 text-right text-indigo-600 font-semibold">
                                    {{ pr.max_weight }} kg
                                </td>
                                <td class="py-2 text-right text-gray-600">{{ pr.best_reps }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
