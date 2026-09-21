<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import { MagnifyingGlassIcon, FunnelIcon, PlusIcon, LifebuoyIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    tickets: Object,
    filters: Object,
    isIT: Boolean
});

// Search & Filter State
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Watchers for Server-side Filtering
watch(search, debounce((value) => {
    router.get(route('admin.tickets.index'), { search: value, status: statusFilter.value }, { preserveState: true, replace: true });
}, 300));

const filterStatus = (status) => {
    statusFilter.value = status;
    router.get(route('admin.tickets.index'), { search: search.value, status: status }, { preserveState: true, replace: true });
};

// Utils
const statusColor = (status) => {
    switch(status) {
        case 'Open': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'In Progress': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'Resolved': return 'bg-green-100 text-green-800 border-green-200';
        case 'Closed': return 'bg-gray-100 text-gray-800 border-gray-200';
        default: return 'bg-gray-50 text-gray-600';
    }
};

const priorityColor = (priority) => {
    switch(priority) {
        case 'Critical': return 'text-red-600 font-bold bg-red-50 px-2 py-0.5 rounded border border-red-100';
        case 'High': return 'text-orange-600 font-bold';
        case 'Medium': return 'text-blue-600';
        default: return 'text-gray-500';
    }
};
</script>

<template>
    <Head title="IT Support Tickets" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <LifebuoyIcon class="w-6 h-6 text-indigo-600"/> IT Support Tickets
                </h2>
                <Link :href="route('admin.tickets.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 flex items-center">
                    <PlusIcon class="w-4 h-4 mr-2"/> Buat Tiket Baru
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters -->
                <div class="bg-white p-4 rounded-lg shadow mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="relative w-full md:w-1/3">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <TextInput v-model="search" placeholder="Cari Subject / Kategori..." class="pl-10 w-full" />
                    </div>
                    
                    <div class="flex gap-2 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
                        <!-- Main Tabs -->
                        <div class="flex bg-gray-100 p-1 rounded-lg mr-4 border">
                            <button @click="router.get(route('admin.tickets.index'), { tab: 'all' }, { preserveState: true })" 
                                    :class="!filters.tab || filters.tab === 'all' ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-4 py-1.5 rounded-md text-sm font-bold transition">
                                Semua Tiket
                            </button>
                            <button @click="router.get(route('admin.tickets.index'), { tab: 'assigned' }, { preserveState: true })" 
                                    :class="filters.tab === 'assigned' ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-4 py-1.5 rounded-md text-sm font-bold transition flex items-center gap-1">
                                Tugas Saya
                                <span v-if="filters.tab === 'assigned'" class="w-2 h-2 rounded-full bg-red-500"></span>
                            </button>
                        </div>
                        
                        <!-- Status Filter -->
                        <button @click="filterStatus('')" :class="!statusFilter ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap">
                            All Status
                        </button>
                        <button @click="filterStatus('Open')" :class="statusFilter==='Open' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'" class="px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap">
                            Open
                        </button>
                        <button @click="filterStatus('In Progress')" :class="statusFilter==='In Progress' ? 'bg-yellow-500 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'" class="px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap">
                            In Progress
                        </button>
                        <button @click="filterStatus('Resolved')" :class="statusFilter==='Resolved' ? 'bg-green-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'" class="px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap">
                            Resolved
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiket</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelapor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada tiket ditemukan.</td>
                                </tr>
                                <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ ticket.subject }}</div>
                                        <div class="text-xs text-gray-500 font-mono mt-1">#{{ ticket.id }} • {{ new Date(ticket.created_at).toLocaleDateString('id-ID', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'}) }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ ticket.category }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span :class="priorityColor(ticket.priority)">{{ ticket.priority }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border" :class="statusColor(ticket.status)">
                                            {{ ticket.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ ticket.reporter.name }}
                                        <div v-if="isIT" class="text-xs text-info-500">{{ ticket.reporter.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div v-if="ticket.assignee" class="flex items-center gap-1 text-indigo-700 font-medium">
                                            <div class="w-2 h-2 rounded-full bg-indigo-500"></div> {{ ticket.assignee.name }}
                                        </div>
                                        <span v-else class="text-gray-400 italic">- Unassigned -</span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm font-medium">
                                        <Link :href="route('admin.tickets.show', ticket.id)" class="text-indigo-600 hover:text-indigo-900 flex justify-center items-center gap-1 bg-indigo-50 px-3 py-1.5 rounded transition hover:bg-indigo-100">
                                            <ChatBubbleLeftRightIcon class="w-4 h-4"/> Detail
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <Pagination :links="tickets.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
