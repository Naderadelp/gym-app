<script setup>
import { ref, onUnmounted } from 'vue';
const props = defineProps({ defaultSeconds: { type: Number, default: 60 } });
const remaining = ref(0); const running = ref(false);
let interval = null;
function start() {
    remaining.value = props.defaultSeconds; running.value = true;
    clearInterval(interval);
    interval = setInterval(() => { if (--remaining.value <= 0) { running.value = false; clearInterval(interval); } }, 1000);
}
function reset() { clearInterval(interval); running.value = false; remaining.value = 0; }
onUnmounted(() => clearInterval(interval));
</script>
<template>
    <div class="flex items-center gap-2">
        <button v-if="!running" @click="start" type="button" class="text-xs border border-indigo-300 text-indigo-600 hover:bg-indigo-50 px-2 py-1 rounded-md">Rest {{ defaultSeconds }}s</button>
        <div v-else class="flex items-center gap-2">
            <span class="font-mono text-sm font-semibold" :class="remaining <= 5 ? 'text-red-500' : 'text-indigo-600'">{{ remaining }}s</span>
            <button @click="reset" type="button" class="text-xs text-gray-400 hover:text-gray-600">✕</button>
        </div>
    </div>
</template>
