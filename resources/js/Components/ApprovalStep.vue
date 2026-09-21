<script setup>
// Props
defineProps({
    status: String,
    icon: Object,
    stepColor: String,
    approverName: String,
    actionDate: String,
});
</script>
<template>
    <div :class="[stepColor, 'flex flex-col items-center w-32']">
        <div class="flex items-center justify-center w-10 h-10 bg-white rounded-full border-4 border-white shadow-sm z-10">
            <component :is="icon" class="w-5 h-5 text-white"/>
        </div>
        <div class="mt-4 text-center">
            <p class="text-sm font-bold text-gray-900">{{ status }}</p>
            <p class="text-xs text-gray-500">{{ approverName }}</p>
            <p class="text-[10px] text-gray-400">{{ actionDate }}</p>
        </div>
    </div>
</template>


