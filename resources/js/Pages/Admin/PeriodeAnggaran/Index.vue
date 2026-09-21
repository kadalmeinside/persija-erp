<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    PlusIcon, PencilSquareIcon, TrashIcon, MagnifyingGlassIcon,
    CalendarDaysIcon
} from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    periodes: Object,
    filters: Object
});

const search = ref(props.filters.search);

watch(search, debounce((value) => {
    router.get(route('admin.periode-anggaran.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    nama_periode: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    is_active: false
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (periode) => {
    isEditing.value = true;
    form.id = periode.id;
    form.nama_periode = periode.nama_periode;
    form.tanggal_mulai = periode.tanggal_mulai;
    form.tanggal_selesai = periode.tanggal_selesai;
    form.is_active = Boolean(periode.is_active);
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.periode-anggaran.update', form.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('admin.periode-anggaran.store'), {
            onSuccess: () => showModal.value = false
        });
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
};
</script>

<template>
    <Head title="Manajemen Periode Anggaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Periode Anggaran</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex justify-between items-center">
                    <div class="relative w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400"/>
                        </span>
                        <input v-model="search" type="text" placeholder="Cari Periode..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <PrimaryButton @click="openCreateModal">
                        <PlusIcon class="w-5 h-5 mr-2"/> Tambah Periode
                    </PrimaryButton>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Periode</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="periodes.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">Tidak ada data.</td>
                            </tr>
                            <tr v-for="periode in periodes.data" :key="periode.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600">
                                            <CalendarDaysIcon class="w-4 h-4"/>
                                        </div>
                                        <div class="ml-4 text-sm font-medium text-gray-900">{{ periode.nama_periode }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(periode.tanggal_mulai) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(periode.tanggal_selesai) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="periode.is_active" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Non-Aktif</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button @click="openEditModal(periode)" class="text-indigo-600 hover:text-indigo-900"><PencilSquareIcon class="w-5 h-5"/></button>
                                    <button @click="deletePeriode(periode.id)" class="text-red-600 hover:text-red-900"><TrashIcon class="w-5 h-5"/></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="periodes.links" class="p-6 border-t border-gray-200" />
                </div>
            </div>
        </div>

        <!-- MODAL FORM -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Edit Periode' : 'Tambah Periode Baru' }}</h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nama Periode" />
                        <TextInput v-model="form.nama_periode" class="w-full mt-1" placeholder="Contoh: Tahun Anggaran 2026" />
                        <InputError :message="form.errors.nama_periode" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Tanggal Mulai" />
                            <TextInput type="date" v-model="form.tanggal_mulai" class="w-full mt-1" />
                            <InputError :message="form.errors.tanggal_mulai" />
                        </div>
                        <div>
                            <InputLabel value="Tanggal Selesai" />
                            <TextInput type="date" v-model="form.tanggal_selesai" class="w-full mt-1" />
                            <InputError :message="form.errors.tanggal_selesai" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" id="active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="active" class="text-sm text-gray-700">Set sebagai Periode Aktif</label>
                    </div>
                    <p class="text-xs text-gray-500">Jika diaktifkan, periode aktif lainnya akan otomatis dinonaktifkan.</p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">{{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
