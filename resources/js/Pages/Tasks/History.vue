<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    MagnifyingGlassIcon, 
    ArrowLeftIcon,
    ViewColumnsIcon,
    ArchiveBoxIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    tasks: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

// Search functionality with debounce
let searchTimeout;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.tasks.history'), { search: value }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

const getStatusColor = (status, archived_at) => {
    if (archived_at) return 'bg-gray-100 text-gray-800 border-gray-200';
    
    switch(status) {
        case 'To Do': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'In Progress': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Review': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'Done': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getPriorityColor = (priority) => {
    switch(priority) {
        case 'High': return 'text-red-600 bg-red-50 border-red-100';
        case 'Medium': return 'text-amber-600 bg-amber-50 border-amber-100';
        case 'Low': return 'text-green-600 bg-green-50 border-green-100';
        default: return 'text-gray-600 bg-gray-50 border-gray-100';
    }
};
</script>

<template>
    <Head title="Riwayat Tugas (Archive)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-xl sm:text-2xl text-gray-900 tracking-tight flex items-center gap-3">
                        <Link :href="route('admin.tasks.index')" class="p-2 rounded-full hover:bg-gray-100 transition-colors hidden sm:block">
                            <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                        </Link>
                        <span>Riwayat Tugas</span>
                    </h2>
                    <p class="hidden sm:block text-sm text-gray-500 mt-1">Tabel semua tugas termasuk yang sudah diarsipkan.</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('admin.tasks.index')" class="flex bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold items-center justify-center gap-2 shadow-sm transition-all">
                        <ViewColumnsIcon class="w-4 h-4" /> Kanban Board
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Search & Filters -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors" placeholder="Cari judul tugas...">
                </div>
                <div class="text-sm text-gray-500 flex items-center gap-2">
                    <ArchiveBoxIcon class="w-5 h-5" /> Mode Tabel (Arsip & Aktif)
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Judul Tugas</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assignee</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Prioritas</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Batas Waktu</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status & Info</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="task in tasks.data" :key="task.id" class="hover:bg-gray-50 transition-colors" :class="{'bg-gray-50/50': task.archived_at}">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ task.title }}</div>
                                    <div class="text-xs text-gray-500 mt-1 line-clamp-1">{{ task.description || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs mr-3">
                                            {{ task.assignee?.nama_lengkap.charAt(0) || '?' }}
                                        </div>
                                        <div class="text-sm text-gray-700">{{ task.assignee?.nama_lengkap || 'Unknown' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-md border" :class="getPriorityColor(task.priority)">
                                        {{ task.priority }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ task.due_date ? new Date(task.due_date).toLocaleDateString('id-ID') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5 items-start">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-md border" :class="getStatusColor(task.status, task.archived_at)">
                                            {{ task.status }}
                                        </span>
                                        <span v-if="task.archived_at" class="text-[10px] text-gray-400 flex items-center gap-1 font-medium">
                                            <ArchiveBoxIcon class="w-3 h-3" /> Diarsipkan
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="tasks.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                    <ArchiveBoxIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                    Tidak ada riwayat tugas yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6" v-if="tasks.last_page > 1">
                    <div class="flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Menampilkan <span class="font-medium">{{ tasks.from }}</span> s/d <span class="font-medium">{{ tasks.to }}</span> dari <span class="font-medium">{{ tasks.total }}</span> tugas
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <Link v-for="(link, k) in tasks.links" :key="k"
                                          :href="link.url || '#'"
                                          v-html="link.label"
                                          class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                          :class="[
                                              link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                              !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                          ]"
                                          :preserve-scroll="true"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </AuthenticatedLayout>
</template>
