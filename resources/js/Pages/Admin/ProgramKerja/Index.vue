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
    BriefcaseIcon
} from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    programs: Object,
    departemens: Array,
    filters: Object
});

const search = ref(props.filters.search);
const filterDept = ref(props.filters.id_departemen || '');

watch([search, filterDept], debounce(([valSearch, valDept]) => {
    router.get(route('admin.program-kerja.index'), { search: valSearch, id_departemen: valDept }, { preserveState: true, replace: true });
}, 300));

const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    nama_program: '',
    id_departemen: ''
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (program) => {
    isEditing.value = true;
    form.id = program.id;
    form.nama_program = program.nama_program;
    form.id_departemen = program.id_departemen;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.program-kerja.update', form.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('admin.program-kerja.store'), {
            onSuccess: () => showModal.value = false
        });
    }
};

const deleteProgram = (id) => {
    if (confirm('Yakin ingin menghapus program ini?')) {
        router.delete(route('admin.program-kerja.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Program Kerja" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Master Program Kerja</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex gap-2 w-full md:w-auto">
                        <div class="relative w-full md:w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400"/>
                            </span>
                            <input v-model="search" type="text" placeholder="Cari Program..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <select v-model="filterDept" class="border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Departemen</option>
                            <option v-for="dept in departemens" :key="dept.id" :value="dept.id">{{ dept.nama_departemen }}</option>
                        </select>
                    </div>
                    
                    <PrimaryButton @click="openCreateModal">
                        <PlusIcon class="w-5 h-5 mr-2"/> Tambah Program
                    </PrimaryButton>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Program</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Departemen</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="programs.data.length === 0">
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500 italic">Tidak ada data.</td>
                            </tr>
                            <tr v-for="program in programs.data" :key="program.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                            <BriefcaseIcon class="w-4 h-4"/>
                                        </div>
                                        <div class="ml-4 text-sm font-medium text-gray-900">{{ program.nama_program }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <span v-if="program.departemen" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        {{ program.departemen.nama_departemen }}
                                    </span>
                                    <span v-else class="text-red-500 italic">Global (Perlu Update)</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button @click="openEditModal(program)" class="text-indigo-600 hover:text-indigo-900"><PencilSquareIcon class="w-5 h-5"/></button>
                                    <button @click="deleteProgram(program.id)" class="text-red-600 hover:text-red-900"><TrashIcon class="w-5 h-5"/></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="programs.links" class="p-6 border-t border-gray-200" />
                </div>
            </div>
        </div>

        <!-- MODAL FORM -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Edit Program Kerja' : 'Tambah Program Kerja Baru' }}</h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nama Program" />
                        <TextInput v-model="form.nama_program" class="w-full mt-1" placeholder="Contoh: Maintenance Server" />
                        <InputError :message="form.errors.nama_program" />
                    </div>
                    
                    <div>
                        <InputLabel value="Departemen Pemilik" />
                        <select v-model="form.id_departemen" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="" disabled>Pilih Departemen</option>
                            <option v-for="dept in departemens" :key="dept.id" :value="dept.id">{{ dept.nama_departemen }}</option>
                        </select>
                        <InputError :message="form.errors.id_departemen" />
                        <p class="text-xs text-gray-500 mt-1">Program ini akan terkunci untuk departemen yang dipilih.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">{{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
