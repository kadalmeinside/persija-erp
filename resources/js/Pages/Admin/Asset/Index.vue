<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { MagnifyingGlassIcon, PlusIcon, CalculatorIcon, EyeIcon } from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    assets: Object,
    filters: Object
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.assets.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const showDepreciationModal = ref(false);
const depreciationForm = useForm({
    month: new Date().toISOString().slice(0, 7) // YYYY-MM
});

const runDepreciation = () => {
    depreciationForm.post(route('admin.assets.run-depreciation'), {
        onSuccess: () => showDepreciationModal.value = false
    });
};
</script>

<template>
    <Head title="Aset Tetap" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Aset Tetap (Fixed Assets)</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                        <div class="relative w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
                            </span>
                            <input v-model="search" type="text" placeholder="Cari Aset..." class="pl-10 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex gap-2">
                            <PrimaryButton @click="showDepreciationModal = true" class="bg-green-600 hover:bg-green-700">
                                <CalculatorIcon class="w-4 h-4 mr-2" /> Hitung Penyusutan
                            </PrimaryButton>
                            <Link :href="route('admin.assets.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <PlusIcon class="w-4 h-4 mr-2" /> Registrasi Aset
                            </Link>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Aset</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tgl Perolehan</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Harga Perolehan</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="asset in assets.data" :key="asset.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ asset.kode_aset }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ asset.nama_aset }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ asset.kategori }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ asset.tgl_perolehan }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(asset.harga_perolehan) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        <span :class="{
                                            'bg-green-100 text-green-800': asset.status === 'Active',
                                            'bg-gray-100 text-gray-800': asset.status === 'Fully Depreciated',
                                            'bg-red-100 text-red-800': asset.status === 'Disposed',
                                        }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ asset.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('admin.assets.show', asset.id)" class="text-indigo-600 hover:text-indigo-900">
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="assets.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data aset.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <Pagination :links="assets.links" />
                    </div>

                </div>
            </div>
        </div>

        <!-- Depreciation Modal -->
        <Modal :show="showDepreciationModal" @close="showDepreciationModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Jalankan Penyusutan Bulanan</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Proses ini akan menghitung penyusutan untuk semua aset aktif pada bulan yang dipilih dan membuat jurnal otomatis.
                </p>
                
                <form @submit.prevent="runDepreciation">
                    <div class="mb-6">
                        <InputLabel value="Pilih Bulan" />
                        <TextInput type="month" v-model="depreciationForm.month" class="mt-1 block w-full" required />
                    </div>

                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="showDepreciationModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="depreciationForm.processing">Proses</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
