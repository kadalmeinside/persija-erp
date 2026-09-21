<script setup>
import { computed } from 'vue';
import { CalendarIcon, UserIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    task: Object,
});

const isOverdue = computed(() => {
    if (!props.task.due_date || props.task.status === 'Done') return false;
    const today = new Date();
    today.setHours(0,0,0,0);
    const due = new Date(props.task.due_date);
    return due < today;
});

const priorityColor = computed(() => {
    switch (props.task.priority) {
        case 'High': return 'bg-red-100 text-red-700 border-red-200';
        case 'Medium': return 'bg-orange-100 text-orange-700 border-orange-200';
        case 'Low': return 'bg-green-100 text-green-700 border-green-200';
        default: return 'bg-gray-100 text-gray-700';
    }
});
</script>

<template>
    <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-200 cursor-grab active:cursor-grabbing hover:border-indigo-400 hover:shadow-md transition-all group">
        <div class="flex justify-between items-start mb-2">
            <span :class="['text-xs px-2 py-0.5 rounded-md border font-medium', priorityColor]">
                {{ task.priority }}
            </span>
            <span v-if="task.departemen" class="text-[10px] text-gray-400 truncate max-w-[100px] uppercase font-semibold" :title="task.departemen.nama_departemen">
                {{ task.departemen.nama_departemen }}
            </span>
        </div>
        
        <h4 class="font-medium text-gray-800 text-sm mb-2 line-clamp-2 group-hover:text-indigo-600 transition-colors">{{ task.title }}</h4>
        
        <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100/80">
            <!-- Assignee -->
            <div class="flex items-center gap-1.5 text-xs text-gray-500" :title="'Assigned to: ' + (task.assignee?.nama_lengkap || 'Unknown')">
                <div v-if="task.assignee?.foto_url" class="w-6 h-6 rounded-full overflow-hidden border border-gray-200">
                    <img :src="task.assignee.foto_url" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center border border-gray-200">
                    <UserIcon class="w-3.5 h-3.5" />
                </div>
                <span class="truncate max-w-[80px]">{{ task.assignee?.nama_lengkap?.split(' ')[0] }}</span>
            </div>

            <!-- Due Date -->
            <div v-if="task.due_date" 
                 class="flex items-center gap-1 text-[11px] px-1.5 py-0.5 rounded"
                 :class="isOverdue ? 'bg-red-50 text-red-600 font-bold border border-red-100' : (task.status === 'Done' ? 'text-green-600' : 'text-gray-400')">
                <CalendarIcon class="w-3.5 h-3.5" />
                <span>{{ new Date(task.due_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}</span>
            </div>
        </div>
    </div>
</template>
