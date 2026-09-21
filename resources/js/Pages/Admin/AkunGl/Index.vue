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
    BookOpenIcon, EyeSlashIcon, EyeIcon,
    NoSymbolIcon, CheckCircleIcon
} from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    akunGls:   Object,
    filters:   Object,
    showArsip: Boolean,
});

const search      = ref(props.filters.search);
const tipeFilter  = ref(props.filters.tipe || '');
const showArsip   = ref(props.showArsip ?? false);

const refresh = () => {
    router.get(route('admin.akun-gl.index'), {
        search:     search.value,
        tipe:       tipeFilter.value,
        show_arsip: showArsip.value ? '1' : '0',
    }, { preserveState: true, replace: true });
};

watch(search, debounce(refresh, 300));
watch([tipeFilter, showArsip], refresh);

// ── Form Modal ────────────────────────────────────────────────────────────────
const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id:       null,
    kode_akun:'',
    nama_akun:'',
    tipe_akun:'Biaya',
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.tipe_akun = 'Biaya';
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (akun) => {
    isEditing.value = true;
    form.id        = akun.id;
    form.kode_akun = akun.kode_akun;
    form.nama_akun = akun.nama_akun;
    form.tipe_akun = akun.tipe_akun;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.akun-gl.update', form.id), {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post(route('admin.akun-gl.store'), {
            onSuccess: () => showModal.value = false,
        });
    }
};

// ── Archive / Restore / Delete ────────────────────────────────────────────────
const archiveAkun = (akun) => {
    if (confirm(`Nonaktifkan akun "${akun.kode_akun} - ${akun.nama_akun}"?\n\nAkun tidak akan muncul di dropdown transaksi baru. Data jurnal historis tetap aman.`)) {
        router.post(route('admin.akun-gl.archive', akun.id));
    }
};

const restoreAkun = (akun) => {
    if (confirm(`Aktifkan kembali akun "${akun.kode_akun} - ${akun.nama_akun}"?`)) {
        router.post(route('admin.akun-gl.restore', akun.id));
    }
};

const deleteAkun = (akun) => {
    if (confirm(`Hapus permanen akun "${akun.kode_akun}"?\n\nJika sudah digunakan dalam jurnal, penghapusan akan ditolak. Gunakan "Nonaktifkan" sebagai gantinya.`)) {
        router.delete(route('admin.akun-gl.destroy', akun.id));
    }
};

// ── Badge Helpers ─────────────────────────────────────────────────────────────
const getTipeBadgeClass = (tipe) => {
    switch (tipe) {
        case 'Aset':       return 'bg-blue-100 text-blue-800';
        case 'Kewajiban':  return 'bg-red-100 text-red-800';
        case 'Ekuitas':    return 'bg-purple-100 text-purple-800';
        case 'Pendapatan': return 'bg-green-100 text-green-800';
        case 'Biaya':      return 'bg-yellow-100 text-yellow-800';
        case 'Biaya Modal':return 'bg-orange-100 text-orange-800';
        default:           return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Chart of Accounts (COA)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Chart of Accounts (COA)</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex gap-2 w-full md:w-auto flex-wrap">
                        <div class="relative w-full md:w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400"/>
                            </span>
                            <input v-model="search" type="text" placeholder="Cari kode atau nama akun..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <select v-model="tipeFilter" class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Tipe</option>
                            <option value="Aset">Aset</option>
                            <option value="Kewajiban">Kewajiban</option>
                            <option value="Ekuitas">Ekuitas</option>
                            <option value="Pendapatan">Pendapatan</option>
                            <option value="Biaya">Biaya</option>
                            <option value="Biaya Modal">Biaya Modal</option>
                        </select>

                        <!-- Toggle Nonaktif -->
                        <button
                            @click="showArsip = !showArsip"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-medium transition"
                            :class="showArsip
                                ? 'bg-red-50 border-red-300 text-red-700'
                                : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'"
                        >
                            <component :is="showArsip ? EyeIcon : EyeSlashIcon" class="w-4 h-4" />
                            {{ showArsip ? 'Sembunyikan Nonaktif' : 'Tampilkan Nonaktif' }}
                        </button>
                    </div>

                    <PrimaryButton @click="openCreateModal">
                        <PlusIcon class="w-5 h-5 mr-2"/> Tambah Akun
                    </PrimaryButton>
                </div>

                <!-- Info banner -->
                <div v-if="showArsip" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-800 flex items-center gap-2">
                    <NoSymbolIcon class="w-5 h-5 text-red-500 flex-shrink-0"/>
                    Menampilkan termasuk akun GL yang dinonaktifkan. Akun nonaktif tidak muncul di dropdown transaksi namun semua jurnal historisnya tetap valid.
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode Akun</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Akun</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Saldo Normal</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="akunGls.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 italic">Tidak ada data akun.</td>
                            </tr>
                            <tr v-for="akun in akunGls.data" :key="akun.id"
                                class="hover:bg-gray-50 transition"
                                :class="!akun.is_active ? 'opacity-60 bg-red-50/30' : ''"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-bold" :class="akun.is_active ? 'text-indigo-600' : 'text-gray-400'">
                                    {{ akun.kode_akun }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ akun.nama_akun }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getTipeBadgeClass(akun.tipe_akun)">
                                        {{ akun.tipe_akun }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ akun.saldo_normal }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="akun.is_active"
                                          class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800 items-center gap-1">
                                        <CheckCircleIcon class="w-3 h-3"/> Aktif
                                    </span>
                                    <span v-else
                                          class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-700 items-center gap-1">
                                        <NoSymbolIcon class="w-3 h-3"/> Nonaktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <!-- Edit -->
                                    <button @click="openEditModal(akun)" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                        <PencilSquareIcon class="w-5 h-5"/>
                                    </button>

                                    <!-- Nonaktifkan / Aktifkan -->
                                    <button v-if="akun.is_active" @click="archiveAkun(akun)"
                                            class="text-red-500 hover:text-red-700" title="Nonaktifkan Akun">
                                        <NoSymbolIcon class="w-5 h-5"/>
                                    </button>
                                    <button v-else @click="restoreAkun(akun)"
                                            class="text-green-600 hover:text-green-800" title="Aktifkan Kembali">
                                        <CheckCircleIcon class="w-5 h-5"/>
                                    </button>

                                    <!-- Hapus Permanen -->
                                    <button @click="deleteAkun(akun)" class="text-gray-400 hover:text-red-600" title="Hapus Permanen">
                                        <TrashIcon class="w-5 h-5"/>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="akunGls.links" class="p-6 border-t border-gray-200" />
                </div>
            </div>
        </div>

        <!-- MODAL: Tambah / Edit -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Edit Akun GL' : 'Tambah Akun GL Baru' }}</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <InputLabel value="Kode Akun" />
                            <TextInput v-model="form.kode_akun" class="w-full mt-1" placeholder="1-1001" :disabled="isEditing" />
                            <InputError :message="form.errors.kode_akun" />
                        </div>
                        <div class="col-span-2">
                            <InputLabel value="Nama Akun" />
                            <TextInput v-model="form.nama_akun" class="w-full mt-1" />
                            <InputError :message="form.errors.nama_akun" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Tipe Akun" />
                        <select v-model="form.tipe_akun" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="Aset">Aset</option>
                            <option value="Kewajiban">Kewajiban</option>
                            <option value="Ekuitas">Ekuitas</option>
                            <option value="Pendapatan">Pendapatan</option>
                            <option value="Biaya">Biaya</option>
                            <option value="Biaya Modal">Biaya Modal</option>
                        </select>
                        <InputError :message="form.errors.tipe_akun" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Akun' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
