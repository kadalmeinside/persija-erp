<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { CheckCircleIcon, XCircleIcon, ClockIcon, PencilSquareIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    pengajuan: Object
});

// Helper untuk format mata uang
const formatCurrency = (value) => {
    if (typeof value !== 'number') {
        value = parseFloat(value) || 0;
    }
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

// Helper untuk format tanggal
const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

// Helper untuk status
const statusBadge = computed(() => {
    switch (props.pengajuan.status_global) {
        case 'Pending Approval':
            return 'bg-yellow-100 text-yellow-800';
        case 'Approved':
            return 'bg-blue-100 text-blue-800';
        case 'Paid':
            return 'bg-green-100 text-green-800';
        case 'Rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
});

const statusIcon = computed(() => {
    switch (props.pengajuan.status_global) {
        case 'Pending Approval':
            return ClockIcon;
        case 'Paid':
            return CheckCircleIcon;
        case 'Rejected':
            return XCircleIcon;
        default:
            return ClockIcon;
    }
});

</script>

<template>
    <Head :title="`Detail Pengajuan ${pengajuan.nomor_pengajuan}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detail Pengajuan: {{ pengajuan.nomor_pengajuan }}
                </h2>
                <div class="space-x-2">
                    <Link :href="route('admin.pengajuan.index')" 
                          class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        <ArrowUturnLeftIcon class="w-4 h-4 mr-2" />
                        Kembali
                    </Link>
                    <!-- Kita akan tambahkan tombol Edit di sini nanti -->
                    <!-- <Link :href="route('admin.pengajuan.edit', pengajuan.id)"
                          class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <PencilSquareIcon class="w-4 h-4 mr-2" />
                        Edit
                    </Link> -->
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- 1. Info Header Pengajuan -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    {{ pengajuan.tipe_pengajuan === 'Langsung' ? 'Payment Request' : 'Cash Advance' }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Diajukan oleh: <span class="font-medium text-gray-700">{{ pengajuan.pengaju.nama_lengkap }}</span>
                                    dari Dept. <span class="font-medium text-gray-700">{{ pengajuan.departemen.nama_departemen }}</span>
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Tanggal: <span class="font-medium text-gray-700">{{ formatDate(pengajuan.tgl_pengajuan) }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <span :class="statusBadge" class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium">
                                    <component :is="statusIcon" class="w-4 h-4 mr-1.5" />
                                    {{ pengajuan.status_global }}
                                </span>
                                <div class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ formatCurrency(pengajuan.total_nominal_diajukan) }}
                                </div>
                            </div>
                        </div>
                        <div v-if="pengajuan.catatan_header" class="mt-4">
                            <p class="text-sm font-medium text-gray-700">Catatan Pengaju:</p>
                            <p class="mt-1 text-sm text-gray-600 bg-gray-50 p-3 rounded-md italic">
                                {{ pengajuan.catatan_header }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2. Rincian Item (Hanya jika 'Langsung') -->
                <div v-if="pengajuan.tipe_pengajuan === 'Langsung'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Rincian Item</h3>
                        <div class="overflow-x-auto border rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akun Biaya</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in pengajuan.detail" :key="item.id">
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ item.deskripsi_item }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ item.akun_gl.kode_akun }} - {{ item.akun_gl.nama_akun }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ item.program_kerja.nama_program }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ formatCurrency(item.nominal_item) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. Riwayat Persetujuan -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Riwayat Persetujuan</h3>
                        <div v-if="pengajuan.log_persetujuan.length > 0">
                            <ul role="list" class="divide-y divide-gray-200">
                                <li v-for="log in pengajuan.log_persetujuan" :key="log.id" class="py-4">
                                    <div class="flex space-x-3">
                                        <div :class="[log.status_aksi === 'Approved' ? 'bg-green-500' : 'bg-red-500', 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white']">
                                            <CheckCircleIcon v-if="log.status_aksi === 'Approved'" class="w-6 h-6" />
                                            <XCircleIcon v-else class="w-6 h-6" />
                                        </div>
                                        <div class="flex-1 space-y-1">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-sm font-medium">{{ log.approver.nama_lengkap }} ({{ log.approver.jabatan }})</h3>
                                                <p class="text-sm text-gray-500">{{ formatDate(log.tgl_aksi) }}</p>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                Status: <span class="font-medium" :class="[log.status_aksi === 'Approved' ? 'text-green-700' : 'text-red-700']">{{ log.status_aksi }}</span>
                                            </p>
                                            <p v-if="log.catatan_approver" class="text-sm text-gray-500 italic bg-gray-50 p-2 rounded">
                                                "{{ log.catatan_approver }}"
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div v-else>
                            <p class="text-sm text-gray-500">Belum ada riwayat persetujuan untuk dokumen ini.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>