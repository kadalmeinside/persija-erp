<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon, LockClosedIcon, LockOpenIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    periods: Object,
    filters: Object
});

const showCreateModal = ref(false);
const form = useForm({
    nama_periode: '',
    tanggal_mulai: '',
    tanggal_selesai: ''
});

const submit = () => {
    form.post(route('admin.accounting-periods.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const closePeriod = (period) => {
    if (confirm(`Apakah Anda yakin ingin menutup periode ${period.nama_periode}? Data tidak akan bisa diubah lagi.`)) {
        router.post(route('admin.accounting-periods.close', period.id));
    }
};

const reopenPeriod = (period) => {
    if (confirm(`Apakah Anda yakin ingin membuka kembali periode ${period.nama_periode}?`)) {
        router.post(route('admin.accounting-periods.reopen', period.id));
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head title="Periode Akuntansi" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Periode Akuntansi</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex-1 max-w-sm">
                        <TextInput
                            v-model="filters.search"
                            type="text"
                            placeholder="Cari periode..."
                            class="w-full"
                            @input="router.get(route('admin.accounting-periods.index'), { search: $event.target.value }, { preserveState: true, replace: true })"
                        />
                    </div>
                    <PrimaryButton @click="showCreateModal = true">
                        <PlusIcon class="w-4 h-4 mr-2" /> Buat Periode Baru
                    </PrimaryButton>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Periode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="period in periods.data" :key="period.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ period.nama_periode }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(period.tanggal_mulai) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(period.tanggal_selesai) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="period.is_closed" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Closed
                                    </span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Open
                                    </span>
                                    <div v-if="period.is_closed" class="text-xs text-gray-400 mt-1">
                                        by {{ period.closer?.name }} <br> {{ formatDate(period.closed_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button 
                                        v-if="!period.is_closed" 
                                        @click="closePeriod(period)" 
                                        class="text-red-600 hover:text-red-900 inline-flex items-center"
                                    >
                                        <LockClosedIcon class="w-4 h-4 mr-1" /> Tutup Buku
                                    </button>
                                    <button 
                                        v-else 
                                        @click="reopenPeriod(period)" 
                                        class="text-green-600 hover:text-green-900 inline-flex items-center"
                                    >
                                        <LockOpenIcon class="w-4 h-4 mr-1" /> Buka Kembali
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="periods.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada data periode.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-4">
                    <!-- Add pagination component here if needed -->
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Buat Periode Baru</h2>
                
                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <InputLabel value="Nama Periode" />
                        <TextInput v-model="form.nama_periode" type="text" class="mt-1 block w-full" placeholder="Contoh: Januari 2025" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <InputLabel value="Tanggal Mulai" />
                            <TextInput v-model="form.tanggal_mulai" type="date" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Tanggal Selesai" />
                            <TextInput v-model="form.tanggal_selesai" type="date" class="mt-1 block w-full" required />
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="showCreateModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
