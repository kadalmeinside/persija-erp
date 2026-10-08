<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    CheckBadgeIcon, 
    BanknotesIcon, 
    CalendarIcon, 
    DocumentTextIcon,
    ChevronRightIcon,
    ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    approvals: Object,
    filters: Object,
});

const currentStatus = ref(props.filters?.status || 'Pending');

watch(currentStatus, (newVal) => {
    router.get(route('admin.approval-center.index'), { status: newVal }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

const getIcon = (type) => {
    switch(type) {
        case 'Pengajuan': return BanknotesIcon;
        case 'Cuti': return CalendarIcon;
        case 'Pinjaman': return DocumentTextIcon;
        default: return ClipboardDocumentCheckIcon;
    }
};

const formatCurrency = (value) => {
    if (!value) return null;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Approval Center" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <CheckBadgeIcon class="w-6 h-6 text-red-600" />
                    Approval Center Terpadu
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filter Status -->
                <div class="mb-6 flex space-x-2 border-b border-gray-200">
                    <button 
                        @click="currentStatus = 'Pending'"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2',
                            currentStatus === 'Pending' 
                                ? 'border-red-600 text-red-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Menunggu Persetujuan
                    </button>
                    <button 
                        @click="currentStatus = 'Approved'"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2',
                            currentStatus === 'Approved' 
                                ? 'border-red-600 text-red-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Riwayat Disetujui
                    </button>
                    <button 
                        @click="currentStatus = 'Rejected'"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2',
                            currentStatus === 'Rejected' 
                                ? 'border-red-600 text-red-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Riwayat Ditolak
                    </button>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <div v-if="approvals.data.length === 0" class="text-center py-12">
                            <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Saat ini tidak ada dokumen yang perlu di-approve.
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <Link 
                                v-for="item in approvals.data" 
                                :key="item.id"
                                :href="item.route_url || '#'"
                                class="block border border-gray-200 rounded-lg p-4 hover:border-red-300 hover:bg-red-50 transition-colors duration-150"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-4">
                                        <div class="mt-1 bg-red-100 p-2 rounded-lg text-red-600">
                                            <component :is="getIcon(item.type)" class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ item.type }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    Diajukan oleh <span class="font-medium text-gray-700">{{ item.applicant }}</span>
                                                </span>
                                            </div>
                                            <h4 class="text-base font-semibold text-gray-900 mt-1">
                                                {{ item.document?.title || 'Dokumen Tidak Diketahui' }}
                                            </h4>
                                            
                                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                                                <div v-if="item.document?.amount" class="flex items-center font-medium text-gray-900">
                                                    {{ formatCurrency(item.document.amount) }}
                                                </div>
                                                <div v-if="item.document?.start_date" class="flex items-center text-gray-500">
                                                    Tanggal: {{ formatDate(item.document.start_date) }}
                                                </div>
                                                <div class="flex items-center text-gray-500">
                                                    Aksi: {{ item.label_aksi }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-center pt-2">
                                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div class="mt-6" v-if="approvals.links && approvals.data.length > 0">
                            <Pagination :links="approvals.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
