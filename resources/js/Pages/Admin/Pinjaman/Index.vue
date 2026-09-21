<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputCurrency from '@/Components/InputCurrency.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { PlusIcon, CheckCircleIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    pinjaman: Object,
    employees: Array,
    filters: Object
});

const form = useForm({
    id_karyawan: '',
    jumlah_pinjaman: 0,
    tenor_bulan: 3,
    tanggal_pengajuan: new Date().toISOString().split('T')[0],
    keterangan: ''
});

const showModal = ref(false);

const employeeOptions = computed(() => {
    return props.employees.map(emp => ({
        value: emp.id,
        label: emp.nama_lengkap
    }));
});

const submit = () => {
    form.post(route('admin.pinjaman.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        }
    });
};

// Approve action moved to Show.vue with Level-based workflow

const deleteLoan = (id) => {
    if (confirm('Hapus data pinjaman ini?')) {
        useForm({}).delete(route('admin.pinjaman.destroy', id));
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    // Use user locale date time format
    return new Date(dateString).toLocaleDateString('id-ID', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
};

</script>

<template>
    <Head title="Pinjaman Karyawan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Pinjaman Karyawan (Kasbon)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        
                        <div class="flex justify-between mb-6">
                            <div class="flex gap-2">
                                <!-- Search could go here -->
                            </div>
                            <PrimaryButton @click="showModal = true">
                                <PlusIcon class="w-5 h-5 mr-2" />
                                Ajukan Pinjaman
                            </PrimaryButton>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Karyawan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tenor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Angsuran/Bln</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="loan in pinjaman.data" :key="loan.id">
                                        <td class="px-6 py-4 whitespace-nowrap font-medium">{{ loan.karyawan?.nama_lengkap }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatDate(loan.tanggal_pengajuan) }}</div>
                                            <div class="text-xs text-gray-500">{{ loan.created_at ? new Date(loan.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) + ' WIB' : '' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(loan.jumlah_pinjaman) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ loan.tenor_bulan }} Bulan</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(loan.jumlah_angsuran_per_bulan) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'px-2 py-1 text-xs rounded-full': true,
                                                'bg-yellow-100 text-yellow-800': loan.status === 'Draft',
                                                'bg-green-100 text-green-800': loan.status === 'Approved',
                                                'bg-blue-100 text-blue-800': loan.status === 'Paid Off',
                                            }">
                                                {{ loan.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end gap-2">
                                                <Link :href="route('admin.pinjaman.show', loan.id)" class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-md border border-blue-200 transition-colors" title="Lihat Detail">
                                                    <EyeIcon class="w-4 h-4 mr-1" />
                                                    <span class="text-xs">Detail</span>
                                                </Link>
                                                <!-- Approval dipindahkan ke halaman Detail -->
                                                <button v-if="loan.status === 'Draft'" @click="deleteLoan(loan.id)" class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 hover:bg-red-100 rounded-md border border-red-200 transition-colors" title="Hapus">
                                                    <TrashIcon class="w-4 h-4 mr-1" />
                                                    <span class="text-xs">Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="pinjaman.data.length === 0">
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada data pinjaman.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Create -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Ajukan Pinjaman Baru</h2>
                
                <div class="mb-4">
                    <InputLabel value="Karyawan" />
                    <SearchableSelect
                        v-model="form.id_karyawan"
                        :options="employeeOptions"
                        placeholder="Cari Karyawan..."
                        class="mt-1"
                    />
                    <div v-if="form.errors.id_karyawan" class="text-red-500 text-xs mt-1">{{ form.errors.id_karyawan }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <InputLabel value="Jumlah Pinjaman" />
                        <InputCurrency v-model="form.jumlah_pinjaman" class="mt-1 block w-full" />
                        <div v-if="form.errors.jumlah_pinjaman" class="text-red-500 text-xs mt-1">{{ form.errors.jumlah_pinjaman }}</div>
                    </div>
                    <div>
                        <InputLabel value="Tenor (Bulan)" />
                        <TextInput v-model="form.tenor_bulan" type="number" min="1" max="24" class="mt-1 block w-full" />
                        <div v-if="form.errors.tenor_bulan" class="text-red-500 text-xs mt-1">{{ form.errors.tenor_bulan }}</div>
                    </div>
                </div>

                <div class="mb-4">
                    <InputLabel value="Tanggal Pengajuan" />
                    <TextInput v-model="form.tanggal_pengajuan" type="date" class="mt-1 block w-full" />
                </div>

                <div class="mb-4">
                    <InputLabel value="Keterangan" />
                    <TextInput v-model="form.keterangan" type="text" class="mt-1 block w-full" placeholder="Keperluan..." />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
