<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue'; // <-- Import komponen paginasi
import { Head, Link } from '@inertiajs/vue3';
import { EyeIcon, PencilSquareIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    pengajuan: Object // Ini adalah objek paginasi dari controller
});

// Helper untuk format mata uang
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

// Helper untuk format tanggal
const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

// Helper untuk status badge
const statusBadge = (status) => {
    switch (status) {
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
};

</script>

<template>
    <Head title="Daftar Pengajuan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Pengajuan</h2>
                
                <!-- Tombol 'Buat Pengajuan' di header -->
                <Link 
                    v-if="$page.props.auth.user.permissions.includes('pengajuan.create')"
                    :href="route('admin.pengajuan.create')" 
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Buat Pengajuan Baru
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Notifikasi Sukses -->
                        <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                            {{ $page.props.flash.success }}
                        </div>
                        
                        <!-- Notifikasi Error -->
                        <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                            {{ $page.props.flash.error }}
                        </div>

                        <!-- Tabel Daftar Pengajuan -->
                        <div class="overflow-x-auto border rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pengaju</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departemen</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="pengajuan.data.length === 0">
                                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Belum ada data pengajuan.
                                        </td>
                                    </tr>
                                    <tr v-for="item in pengajuan.data" :key="item.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <Link :href="route('admin.pengajuan.show', item.id)" class="text-indigo-600 hover:text-indigo-900">
                                                {{ item.nomor_pengajuan }}
                                            </Link>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(item.tgl_pengajuan) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ item.pengaju.nama_lengkap }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.departemen.nama_departemen }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.tipe_pengajuan }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">{{ formatCurrency(item.total_nominal_diajukan) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span :class="statusBadge(item.status_global)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ item.status_global }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-3">
                                                <Link :href="route('admin.pengajuan.show', item.id)" class="text-indigo-600 hover:text-indigo-900" title="Lihat Detail">
                                                    <EyeIcon class="w-5 h-5" />
                                                </Link>
                                                
                                                <!-- Tombol Edit (Hanya tampil jika 'Pending Approval' dan punya izin) -->
                                                <Link 
                                                    v-if="item.status_global === 'Pending Approval' && $page.props.auth.user.permissions.includes('pengajuan.create')" 
                                                    :href="route('admin.pengajuan.edit', item.id)" 
                                                    class="text-gray-400 hover:text-gray-700" 
                                                    title="Edit">
                                                    <PencilSquareIcon class="w-5 h-5" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginasi -->
                        <Pagination :links="pengajuan.links" class="mt-6" />

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>