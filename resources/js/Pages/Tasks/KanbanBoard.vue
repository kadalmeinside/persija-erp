<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import TaskCard from './Partials/TaskCard.vue';
import TaskModal from './Partials/TaskModal.vue';

const props = defineProps({
    tasks: Array,
    assigneeOptions: Array,
    departemenOptions: Array,
    programKerjaOptions: Array,
    currentKaryawanId: Number,
});

const columns = ['To Do', 'In Progress', 'Review', 'Done'];

// State
const showModal = ref(false);
const editingTask = ref(null);
const draggedTask = ref(null);

const tasksByStatus = computed(() => {
    const grouped = {};
    columns.forEach(col => grouped[col] = []);
    
    props.tasks.forEach(task => {
        if (grouped[task.status]) {
            grouped[task.status].push(task);
        }
    });
    
    return grouped;
});

// Modal Actions
const openCreateModal = (defaultStatus = 'To Do') => {
    editingTask.value = null;
    showModal.value = true;
};

const openEditModal = (task) => {
    editingTask.value = task;
    showModal.value = true;
};

// Drag and Drop Logic
const onDragStart = (task, event) => {
    draggedTask.value = task;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.dropEffect = 'move';
    
    // Add visual feedback
    setTimeout(() => {
        event.target.classList.add('opacity-40');
    }, 0);
};

const onDragEnd = (event) => {
    event.target.classList.remove('opacity-40');
    draggedTask.value = null;
};

const onDragEnter = (status, event) => {
    event.preventDefault();
};

const onDragOver = (status, event) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
};

const onDrop = (status, event) => {
    event.preventDefault();
    if (!draggedTask.value) return;

    const task = draggedTask.value;
    const oldStatus = task.status;
    
    if (oldStatus !== status) {
        // Optimistic UI update
        task.status = status;
        
        // Persist to server
        router.post(route('admin.tasks.updateStatus', task.id), {
            status: status
        }, {
            preserveScroll: true,
            onError: () => {
                // Revert on error
                task.status = oldStatus;
            }
        });
    }
};
</script>

<template>
    <Head title="Manajemen Tugas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-xl sm:text-2xl text-gray-900 tracking-tight">
                        <span class="sm:hidden">Tugas</span>
                        <span class="hidden sm:inline">Kanban Tugas</span>
                    </h2>
                    <p class="hidden sm:block text-sm text-gray-500 mt-1">Kelola dan pantau progres tugas harian tim Anda.</p>
                </div>
                <button @click="openCreateModal('To Do')" class="hidden sm:flex bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-100 text-white px-5 py-2.5 rounded-xl text-sm font-semibold items-center justify-center gap-2 shadow-sm shadow-indigo-200 transition-all">
                    <PlusIcon class="w-5 h-5" /> Buat Tugas Baru
                </button>
            </div>
        </template>

        <!-- Main Kanban Container -->
        <div class="py-4 sm:py-6 h-[calc(100vh-140px)] sm:h-[calc(100vh-180px)] flex flex-col">
            <div class="max-w-full mx-auto w-full flex-1 flex flex-col overflow-hidden">
                
                <!-- Mobile Action Button -->
                <div class="flex sm:hidden justify-end mb-4 shrink-0">
                    <button @click="openCreateModal('To Do')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-sm transition-colors">
                        <PlusIcon class="w-4 h-4 mr-2" /> Buat Tugas
                    </button>
                </div>

                <div class="flex-1 overflow-x-auto overflow-y-hidden pb-4 custom-scrollbar snap-x snap-mandatory">
                    <div class="flex gap-4 sm:gap-6 h-full min-w-max items-start px-2 sm:px-0">
                        
                        <!-- Kanban Column -->
                        <div v-for="status in columns" :key="status" 
                             class="flex flex-col w-[85vw] sm:w-[340px] max-h-full bg-gray-50/80 rounded-2xl border border-gray-200 shadow-sm transition-colors snap-center sm:snap-none"
                             @dragenter="onDragEnter(status, $event)"
                             @dragover="onDragOver(status, $event)"
                             @drop="onDrop(status, $event)">
                            
                            <!-- Column Header -->
                            <div class="px-5 py-4 border-b border-gray-200/70 bg-white/50 backdrop-blur-sm rounded-t-2xl flex justify-between items-center sticky top-0 z-10">
                                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2.5 tracking-wide">
                                    <span class="w-2.5 h-2.5 rounded-full shadow-sm" 
                                          :class="{
                                            'bg-blue-500': status === 'To Do',
                                            'bg-amber-400': status === 'In Progress',
                                            'bg-purple-500': status === 'Review',
                                            'bg-emerald-500': status === 'Done'
                                          }"></span>
                                    {{ status }}
                                </h3>
                                <span class="bg-white text-gray-500 text-xs font-semibold py-1 px-2.5 rounded-full border border-gray-200 shadow-sm">{{ tasksByStatus[status].length }}</span>
                            </div>

                            <!-- Column Body (Scrollable) -->
                            <div class="p-3.5 flex-1 overflow-y-auto space-y-3 min-h-[150px] custom-scrollbar">
                                <TaskCard 
                                    v-for="task in tasksByStatus[status]" 
                                    :key="task.id" 
                                    :task="task" 
                                    draggable="true"
                                    @dragstart="onDragStart(task, $event)"
                                    @dragend="onDragEnd($event)"
                                    @click="openEditModal(task)"
                                />
                                
                                <div v-if="tasksByStatus[status].length === 0" class="h-28 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center text-sm text-gray-400 bg-gray-50/50">
                                    Tarik tugas ke sini
                                </div>
                            </div>
                            
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Task Modal -->
        <TaskModal 
            :show="showModal" 
            :task="editingTask" 
            :assignees="assigneeOptions"
            :departemens="departemenOptions"
            :program-kerjas="programKerjaOptions"
            :current-karyawan-id="currentKaryawanId"
            @close="showModal = false" 
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}
</style>
