<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useClientValidation } from '@/Composables/useClientValidation';
import { 
    PlusIcon, PencilSquareIcon, TrashIcon, MagnifyingGlassIcon,
    BuildingStorefrontIcon
} from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    vendors: Object,
    filters: Object
});

const search = ref(props.filters.search);

watch(search, debounce((value) => {
    router.get(route('admin.vendors.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    nama_vendor: '',
    telepon_vendor: '',
    kategori_vendor: 'Umum',
    alamat_vendor: '',
    is_active: true
});

const { clientErrors, validate, clearClientError, clearAllClientErrors, hasClientErrors } = useClientValidation();

const vendorRules = {
    nama_vendor:     ['required', 'string', 'max:255'],
    kategori_vendor: ['required', 'in:Umum,Material,Jasa'],
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    clearAllClientErrors();
    showModal.value = true;
};

const openEditModal = (vendor) => {
    isEditing.value = true;
    form.id = vendor.id;
    form.nama_vendor = vendor.nama_vendor;
    form.telepon_vendor = vendor.telepon_vendor;
    form.kategori_vendor = vendor.kategori_vendor;
    form.alamat_vendor = vendor.alamat_vendor;
    form.is_active = Boolean(vendor.is_active);
    form.clearErrors();
    clearAllClientErrors();
    showModal.value = true;
};

const submit = () => {
    const errors = validate(form, vendorRules, {
        'nama_vendor.label':     'Nama Vendor',
        'kategori_vendor.label': 'Kategori',
    });
    if (hasClientErrors(errors)) return;

    if (isEditing.value) {
        form.put(route('admin.vendors.update', form.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('admin.vendors.store'), {
            onSuccess: () => showModal.value = false
        });
    }
};

const deleteVendor = (id) => {
    if (confirm('Yakin ingin menghapus vendor ini?')) {
        router.delete(route('admin.vendors.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen Vendor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Vendor</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex justify-between items-center">
                    <div class="relative w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400"/>
                        </span>
                        <input v-model="search" type="text" placeholder="Cari Vendor..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <PrimaryButton @click="openCreateModal">
                        <PlusIcon class="w-5 h-5 mr-2"/> Tambah Vendor
                    </PrimaryButton>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Vendor</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kontak</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="vendors.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 italic">Tidak ada data vendor.</td>
                            </tr>
                            <tr v-for="vendor in vendors.data" :key="vendor.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">{{ vendor.kode_vendor }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600">
                                            <BuildingStorefrontIcon class="w-4 h-4"/>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ vendor.nama_vendor }}</div>
                                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ vendor.alamat_vendor }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ vendor.kategori_vendor }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ vendor.telepon_vendor || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="vendor.is_active" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Non-Aktif</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button @click="openEditModal(vendor)" class="text-indigo-600 hover:text-indigo-900"><PencilSquareIcon class="w-5 h-5"/></button>
                                    <button @click="deleteVendor(vendor.id)" class="text-red-600 hover:text-red-900"><TrashIcon class="w-5 h-5"/></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="vendors.links" class="p-6 border-t border-gray-200" />
                </div>
            </div>
        </div>

        <!-- MODAL FORM -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Edit Vendor' : 'Tambah Vendor Baru' }}</h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nama Vendor" />
                        <TextInput v-model="form.nama_vendor" @input="clearClientError('nama_vendor')" class="w-full mt-1" :class="{'border-red-500': clientErrors.nama_vendor || form.errors.nama_vendor}" />
                        <InputError :message="clientErrors.nama_vendor || form.errors.nama_vendor" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Kategori" />
                            <select v-model="form.kategori_vendor" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="Umum">Umum</option>
                                <option value="Material">Material</option>
                                <option value="Jasa">Jasa</option>
                            </select>
                            <InputError :message="form.errors.kategori_vendor" />
                        </div>
                        <div>
                            <InputLabel value="No. Telepon" />
                            <TextInput v-model="form.telepon_vendor" class="w-full mt-1" />
                            <InputError :message="form.errors.telepon_vendor" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Alamat Lengkap" />
                        <textarea v-model="form.alamat_vendor" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:ring-indigo-500 focus:border-indigo-500" rows="3"></textarea>
                        <InputError :message="form.errors.alamat_vendor" />
                    </div>

                    <div v-if="isEditing" class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" id="active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="active" class="text-sm text-gray-700">Status Aktif</label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">{{ isEditing ? 'Simpan Perubahan' : 'Simpan Vendor' }}</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
