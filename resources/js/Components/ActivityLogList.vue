<script setup>
import { computed } from 'vue';

const props = defineProps({
    activities: {
        type: Array,
        default: () => []
    }
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short'
    });
};

const getEventColor = (event) => {
    switch (event) {
        case 'created': return 'bg-green-100 text-green-800';
        case 'updated': return 'bg-blue-100 text-blue-800';
        case 'deleted': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const formatChanges = (properties) => {
    if (!properties || !properties.attributes) return null;
    
    // Only show changed attributes (simple logic)
    // If there is 'old' data, we can compare. But standard log usually has 'attributes' (new) and 'old' (old).
    
    const changes = [];
    const attributes = properties.attributes;
    const old = properties.old || {};

    for (const key in attributes) {
        // Skip timestamps and some internal fields
        if (['updated_at', 'created_at', 'id'].includes(key)) continue;

        changes.push({
            key: key,
            from: old[key] !== undefined ? old[key] : '-',
            to: attributes[key]
        });
    }

    return changes;
};
</script>

<template>
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            <li v-for="(activity, index) in activities" :key="activity.id">
                <div class="relative pb-8">
                    <span v-if="index !== activities.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                    <div class="relative flex space-x-3">
                        <div>
                            <span :class="[getEventColor(activity.event), 'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white']">
                                <svg v-if="activity.event === 'created'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <svg v-else-if="activity.event === 'updated'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                            <div>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium text-gray-900">{{ activity.causer ? activity.causer.name : 'System' }}</span>
                                    {{ activity.description }}
                                </p>
                                
                                <!-- Changes Details -->
                                <div v-if="formatChanges(activity.properties) && formatChanges(activity.properties).length > 0" class="mt-2 text-xs bg-gray-50 border border-gray-100 rounded-lg overflow-hidden">
                                    <!-- Mobile View: Stacked -->
                                    <div class="block md:hidden divide-y divide-gray-100">
                                        <div v-for="change in formatChanges(activity.properties)" :key="change.key" class="p-3">
                                            <p class="font-bold text-gray-700 mb-1">{{ change.key }}</p>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <span class="text-[10px] uppercase text-gray-400 font-bold block">Dari</span>
                                                    <span class="text-red-600 break-words">{{ change.from }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-[10px] uppercase text-gray-400 font-bold block">Menjadi</span>
                                                    <span class="text-green-600 break-words">{{ change.to }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Desktop View: Table -->
                                    <table class="hidden md:table w-full">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="text-left font-semibold text-gray-600 p-2 pl-3">Field</th>
                                                <th class="text-left font-semibold text-gray-600 p-2">Dari</th>
                                                <th class="text-left font-semibold text-gray-600 p-2">Menjadi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="change in formatChanges(activity.properties)" :key="change.key">
                                                <td class="p-2 pl-3 font-medium text-gray-700">{{ change.key }}</td>
                                                <td class="p-2 text-red-600 truncate max-w-xs">{{ change.from }}</td>
                                                <td class="p-2 text-green-600 truncate max-w-xs">{{ change.to }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                <time :datetime="activity.created_at">{{ formatDate(activity.created_at) }}</time>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
            <li v-if="activities.length === 0">
                <div class="text-center py-4 text-gray-500 text-sm">
                    Belum ada riwayat aktivitas.
                </div>
            </li>
        </ul>
    </div>
</template>
