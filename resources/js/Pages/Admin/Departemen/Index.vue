<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useClientValidation } from '@/Composables/useClientValidation';
import { 
    PlusIcon, PencilSquareIcon, TrashIcon, MagnifyingGlassIcon,
    BuildingOfficeIcon
} from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    departemens: Object,
    expenseAccounts: Array,
    karyawans: Array,
    filters: Object
});

const expenseAccountOptions = computed(() => {
    return props.expenseAccounts.map(acc => ({
        value: acc.id,
        label: `${acc.kode_akun} - ${acc.nama_akun}`
    }));
});

const karyawanOptions = computed(() => {
    if (!props.karyawans) return [];
    return props.karyawans.map(k => ({
        value: k.id,
        label: `${k.nama_lengkap} ${k.jabatan ? '('+k.jabatan+')' : ''}`
    }));
});

const search = ref(props.filters.search);

watch(search, debounce((val) => {
    router.get(route('admin.departemen.index'), { search: val }, { preserveState: true, replace: true });
}, 300));

const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    nama_departemen: '',
    id_akun_beban_gaji: null,
    id_karyawan_kepala: null
});

const { clientErrors, validate, clearClientError, clearAllClientErrors, hasClientErrors } = useClientValidation();

const departemenRules = {
    nama_departemen: ['required', 'string', 'max:255'],
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    clearAllClientErrors();
    showModal.value = true;
};

const openEditModal = (dept) => {
    isEditing.value = true;
    form.id = dept.id;
    form.nama_departemen = dept.nama_departemen;
    form.id_akun_beban_gaji = dept.id_akun_beban_gaji;
    form.id_karyawan_kepala = dept.id_karyawan_kepala;
    form.clearErrors();
    clearAllClientErrors();
    showModal.value = true;
};

const submit = () => {
    const errors = validate(form, departemenRules, {
        'nama_departemen.label': 'Nama Departemen',
    });
    if (hasClientErrors(errors)) return;

    if (isEditing.value) {
        form.put(route('admin.departemen.update', form.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('admin.departemen.store'), {
            onSuccess: () => showModal.value = false
        });
    }
};

const deleteDept = (id) => {
    if (confirm('Yakin ingin menghapus departemen ini?')) {
        router.delete(route('admin.departemen.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Departemen" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Master Departemen</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex justify-between items-center">
                    <div class="relative w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400"/>
                        </span>
                        <input v-model="search" type="text" placeholder="Cari Departemen..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <PrimaryButton @click="openCreateModal">
                        <PlusIcon class="w-5 h-5 mr-2"/> Tambah Departemen
                    </PrimaryButton>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Departemen</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kepala Departemen</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="departemens.data.length === 0">
                                <td colspan="2" class="px-6 py-4 text-center text-gray-500 italic">Tidak ada data.</td>
                            </tr>
                            <tr v-for="dept in departemens.data" :key="dept.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-orange-100 rounded-full flex items-center justify-center text-orange-600">
                                            <BuildingOfficeIcon class="w-4 h-4"/>
                                        </div>
                                        <div class="ml-4 text-sm font-medium text-gray-900">{{ dept.nama_departemen }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium">{{ dept.kepala?.nama_lengkap || '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ dept.kepala?.jabatan || 'Belum diatur' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button @click="openEditModal(dept)" class="text-indigo-600 hover:text-indigo-900"><PencilSquareIcon class="w-5 h-5"/></button>
                                    <button @click="deleteDept(dept.id)" class="text-red-600 hover:text-red-900"><TrashIcon class="w-5 h-5"/></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="departemens.links" class="p-6 border-t border-gray-200" />
                </div>
            </div>
        </div>

        <!-- MODAL FORM -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Edit Departemen' : 'Tambah Departemen Baru' }}</h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nama Departemen" />
                        <TextInput v-model="form.nama_departemen" @input="clearClientError('nama_departemen')" class="w-full mt-1" :class="{'border-red-500': clientErrors.nama_departemen || form.errors.nama_departemen}" placeholder="Contoh: Information Technology" />
                        <InputError :message="clientErrors.nama_departemen || form.errors.nama_departemen" />
                    </div>

                    <div>
                        <InputLabel value="Akun Beban Gaji (Opsional)" />
                        <SearchableSelect
                            v-model="form.id_akun_beban_gaji"
                            :options="expenseAccountOptions"
                            placeholder="Pilih Akun Beban..."
                            class="mt-1"
                        />
                        <p class="text-xs text-gray-500 mt-1">Jika kosong, akan menggunakan akun default sistem.</p>
                        <InputError :message="form.errors.id_akun_beban_gaji" />
                    </div>

                    <div>
                        <InputLabel value="Kepala Departemen (Manajer)" />
                        <SearchableSelect
                            v-model="form.id_karyawan_kepala"
                            :options="karyawanOptions"
                            placeholder="Pilih Kepala Departemen..."
                            class="mt-1"
                        />
                        <p class="text-xs text-gray-500 mt-1">Karyawan ini otomatis akan memiliki role Manajer Departemen untuk persetujuan (approval) dan manajemen tugas.</p>
                        <InputError :message="form.errors.id_karyawan_kepala" />
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
