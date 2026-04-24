<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
const props = defineProps({ startedAt: String });
const elapsed = ref('00:00:00');
let interval = null;
function update() {
    const diff = Math.floor((Date.now() - new Date(props.startedAt).getTime()) / 1000);
    elapsed.value = [Math.floor(diff/3600), Math.floor((diff%3600)/60), diff%60].map(n => String(n).padStart(2,'0')).join(':');
}
onMounted(() => { update(); interval = setInterval(update, 1000); });
onUnmounted(() => clearInterval(interval));
</script>
<template><span class="font-mono text-2xl font-bold tracking-wider text-indigo-600">{{ elapsed }}</span></template>
