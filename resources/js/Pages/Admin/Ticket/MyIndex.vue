<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import { MagnifyingGlassIcon, PlusIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    tickets: Object,
    filters: Object
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

watch(search, debounce((value) => {
    router.get(route('admin.tickets.my-requests'), { search: value, status: statusFilter.value }, { preserveState: true, replace: true });
}, 300));

const filterStatus = (status) => {
    statusFilter.value = status;
    router.get(route('admin.tickets.my-requests'), { search: search.value, status: status }, { preserveState: true, replace: true });
};

const statusColor = (status) => {
    switch(status) {
        case 'Open': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'In Progress': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'Resolved': return 'bg-green-100 text-green-800 border-green-200';
        case 'Closed': return 'bg-gray-100 text-gray-800 border-gray-200';
        default: return 'bg-gray-50 text-gray-600';
    }
};
</script>

<template>
    <Head title="Tiket Saya" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tiket Saya
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Toolbar Area -->
                <div class="bg-white p-4 rounded-lg shadow mb-6 flex flex-col lg:flex-row items-center justify-between gap-4">
                    
                    <!-- Search & Filter Group -->
                    <div class="flex flex-col md:flex-row items-center gap-4 w-full lg:w-auto">
                        <div class="relative w-full md:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput v-model="search" placeholder="Cari Tiket Anda..." class="pl-10 w-full" />
                        </div>
                        
                        <div class="flex gap-2 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
                            <button @click="filterStatus('')" :class="!statusFilter ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-3 py-2 rounded-md text-sm font-medium transition whitespace-nowrap">
                                Semua
                            </button>
                            <button @click="filterStatus('Open')" :class="statusFilter==='Open' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'" class="px-3 py-2 rounded-md text-sm font-medium transition whitespace-nowrap">
                                Open
                            </button>
                            <button @click="filterStatus('In Progress')" :class="statusFilter==='In Progress' ? 'bg-yellow-500 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'" class="px-3 py-2 rounded-md text-sm font-medium transition whitespace-nowrap">
                                On Progress
                            </button>
                            <button @click="filterStatus('Resolved')" :class="statusFilter==='Resolved' ? 'bg-green-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'" class="px-3 py-2 rounded-md text-sm font-medium transition whitespace-nowrap">
                                Resolved
                            </button>
                        </div>
                    </div>

                    <!-- Create Button (Moved Here) -->
                    <Link :href="route('admin.tickets.create')" class="w-full lg:w-auto px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 flex justify-center items-center shadow-sm">
                        <PlusIcon class="w-5 h-5 mr-2"/> Buat Tiket Baru
                    </Link>
                </div>

                <!-- Mobile Card View -->
                <div class="block md:hidden space-y-4">
                    <div v-if="tickets.data.length === 0" class="text-center text-gray-500 text-sm py-4">
                        Anda belum membuat tiket apapun.
                    </div>
                    <div v-for="ticket in tickets.data" :key="ticket.id" class="bg-white p-4 rounded-lg shadow border border-gray-100">
                        <div class="flex justify-between items-start mb-2">
                             <div class="flex-1 pr-2">
                                <Link :href="route('admin.tickets.show', ticket.id)" class="text-indigo-600 font-bold text-sm block">
                                    {{ ticket.subject }}
                                </Link>
                                <div class="text-xs text-gray-500 font-mono mt-1">#{{ ticket.id }} • {{ new Date(ticket.created_at).toLocaleDateString() }}</div>
                             </div>
                             <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-semibold rounded-full border whitespace-nowrap" :class="statusColor(ticket.status)">
                                {{ ticket.status }}
                             </span>
                        </div>
                        
                        <div class="border-t pt-2 mt-2 space-y-2">
                             <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Kategori:</span>
                                <span class="font-medium text-gray-700">{{ ticket.category }}</span>
                             </div>
                             <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Ditangani:</span>
                                <span class="font-medium text-gray-700 flex items-center gap-1">
                                    <template v-if="ticket.assignee">
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div> {{ ticket.assignee.name }}
                                    </template>
                                    <span v-else class="text-gray-400 italic">Menunggu IT...</span>
                                </span>
                             </div>
                        </div>

                        <div class="mt-3 text-right">
                             <Link :href="route('admin.tickets.show', ticket.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-900 flex items-center justify-end gap-1">
                                <ChatBubbleLeftRightIcon class="w-4 h-4"/> Lihat Detail
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiket</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ditangani Oleh</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Anda belum membuat tiket apapun.</td>
                                </tr>
                                <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ ticket.subject }}</div>
                                        <div class="text-xs text-gray-500 font-mono mt-1">#{{ ticket.id }} • {{ new Date(ticket.created_at).toLocaleDateString() }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ ticket.category }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border" :class="statusColor(ticket.status)">
                                            {{ ticket.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div v-if="ticket.assignee" class="flex items-center gap-1">
                                            <div class="w-2 h-2 rounded-full bg-green-500"></div> {{ ticket.assignee.name }}
                                        </div>
                                        <span v-else class="text-gray-400 italic">Menunggu IT...</span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm font-medium">
                                        <Link :href="route('admin.tickets.show', ticket.id)" class="text-indigo-600 hover:text-indigo-900">
                                            <ChatBubbleLeftRightIcon class="w-5 h-5 mx-auto"/>
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
