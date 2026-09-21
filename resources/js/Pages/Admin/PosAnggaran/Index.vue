<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    PlusIcon, TrashIcon, MagnifyingGlassIcon,
    BanknotesIcon, UserGroupIcon, ArchiveBoxIcon,
    ArchiveBoxArrowDownIcon, EyeSlashIcon, EyeIcon
} from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    posAnggarans: Object,
    programs: Array,
    akuns: Array,
    departemens: Array,
    filters: Object,
    showArsip: Boolean,
});

const search        = ref(props.filters.search);
const filterProgram = ref(props.filters.id_program || '');
const showArsip     = ref(props.showArsip ?? false);

watch([search, filterProgram, showArsip], debounce(([valSearch, valProg, valArsip]) => {
    router.get(route('admin.pos-anggaran.index'), {
        search: valSearch,
        id_program: valProg,
        show_arsip: valArsip ? '1' : '0',
    }, { preserveState: true, replace: true });
}, 300));

// ── Modal Form ────────────────────────────────────────────────────────────────
const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    id_program_kerja: '',
    id_akun_gl: '',
    delegasi_departemen: [],
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditDelegasiModal = (pos) => {
    isEditing.value = true;
    form.id = pos.id;
    form.id_program_kerja = pos.id_program_kerja;
    form.id_akun_gl = pos.id_akun_gl;
    form.delegasi_departemen = pos.delegasi.map(d => d.id);
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.pos-anggaran.update', form.id), {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post(route('admin.pos-anggaran.store'), {
            onSuccess: () => showModal.value = false,
        });
    }
};

// ── Archive Modal ─────────────────────────────────────────────────────────────
const showArchiveModal = ref(false);
const archiveTarget    = ref(null);
const archiveForm = useForm({ catatan_arsip: '' });

const openArchiveModal = (pos) => {
    archiveTarget.value = pos;
    archiveForm.catatan_arsip = '';
    showArchiveModal.value = true;
};

const submitArchive = () => {
    archiveForm.post(route('admin.pos-anggaran.archive', archiveTarget.value.id), {
        onSuccess: () => { showArchiveModal.value = false; archiveTarget.value = null; },
    });
};

const restorePos = (pos) => {
    if (confirm(`Aktifkan kembali pos anggaran "${pos.akun_gl?.nama_akun}"?`)) {
        router.post(route('admin.pos-anggaran.restore', pos.id));
    }
};

const deletePos = (id) => {
    if (confirm('Yakin ingin menghapus Pos Anggaran ini? Jika sudah digunakan, hapus tidak akan diizinkan — gunakan Arsipkan sebagai gantinya.')) {
        router.delete(route('admin.pos-anggaran.destroy', id));
    }
};

const selectedProgramDept = computed(() => {
    const prog = props.programs.find(p => p.id === form.id_program_kerja);
    return prog?.departemen?.nama_departemen || '-';
});
</script>

<template>
    <Head title="Master Pos Anggaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Master Pos Anggaran &amp; Delegasi</h2>
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
                            <input v-model="search" type="text" placeholder="Cari Akun..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <select v-model="filterProgram" class="border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 max-w-xs">
                            <option value="">Semua Program</option>
                            <option v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.nama_program }} ({{ prog.departemen?.nama_departemen }})</option>
                        </select>

                        <!-- Toggle Arsip -->
                        <button
                            @click="showArsip = !showArsip"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-medium transition"
                            :class="showArsip
                                ? 'bg-amber-100 border-amber-400 text-amber-800'
                                : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'"
                        >
                            <component :is="showArsip ? EyeIcon : EyeSlashIcon" class="w-4 h-4" />
                            {{ showArsip ? 'Sembunyikan Arsip' : 'Tampilkan Arsip' }}
                        </button>
                    </div>

                    <PrimaryButton @click="openCreateModal">
                        <PlusIcon class="w-5 h-5 mr-2"/> Tambah Pos Anggaran
                    </PrimaryButton>
                </div>

                <!-- Info banner -->
                <div v-if="showArsip" class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800 flex items-center gap-2">
                    <ArchiveBoxIcon class="w-5 h-5 text-amber-600 flex-shrink-0"/>
                    Menampilkan termasuk pos anggaran yang diarsipkan. Pos yang diarsipkan tidak muncul di dropdown transaksi baru namun data historisnya tetap aman.
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Program Kerja</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Akun GL (COA)</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Delegasi Akses</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="posAnggarans.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">Tidak ada data.</td>
                            </tr>
                            <tr v-for="pos in posAnggarans.data" :key="pos.id"
                                class="hover:bg-gray-50"
                                :class="!pos.is_active ? 'opacity-60 bg-amber-50/40' : ''"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ pos.program_kerja?.nama_program }}</div>
                                    <div class="text-xs text-gray-500">{{ pos.program_kerja?.departemen?.nama_departemen }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center"
                                             :class="pos.is_active ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                                            <BanknotesIcon class="w-4 h-4"/>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ pos.akun_gl?.nama_akun }}</div>
                                            <div class="text-xs text-gray-500">{{ pos.akun_gl?.kode_akun }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="pos.is_active"
                                          class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                    <div v-else>
                                        <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">
                                            Diarsipkan
                                        </span>
                                        <div v-if="pos.catatan_arsip" class="text-xs text-gray-400 mt-1 max-w-xs truncate" :title="pos.catatan_arsip">
                                            {{ pos.catatan_arsip }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-if="pos.delegasi.length === 0" class="text-xs text-gray-400 italic">Hanya Dept Pemilik</span>
                                        <span v-for="del in pos.delegasi" :key="del.id"
                                              class="px-2 py-0.5 inline-flex text-xs leading-4 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                            {{ del.nama_departemen }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <!-- Atur Delegasi (hanya jika aktif) -->
                                    <button v-if="pos.is_active" @click="openEditDelegasiModal(pos)"
                                            class="text-indigo-600 hover:text-indigo-900" title="Atur Delegasi">
                                        <UserGroupIcon class="w-5 h-5"/>
                                    </button>

                                    <!-- Arsipkan / Aktifkan -->
                                    <button v-if="pos.is_active" @click="openArchiveModal(pos)"
                                            class="text-amber-600 hover:text-amber-800" title="Arsipkan">
                                        <ArchiveBoxArrowDownIcon class="w-5 h-5"/>
                                    </button>
                                    <button v-else @click="restorePos(pos)"
                                            class="text-green-600 hover:text-green-800" title="Aktifkan Kembali">
                                        <ArchiveBoxIcon class="w-5 h-5"/>
                                    </button>

                                    <!-- Hapus Permanen -->
                                    <button @click="deletePos(pos.id)" class="text-red-600 hover:text-red-900" title="Hapus Permanen">
                                        <TrashIcon class="w-5 h-5"/>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="posAnggarans.links" class="p-6 border-t border-gray-200" />
                </div>
            </div>
        </div>

        <!-- MODAL: Tambah / Edit Delegasi -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Atur Delegasi Pos Anggaran' : 'Tambah Pos Anggaran Baru' }}</h2>

                <div class="space-y-4">
                    <div>
                        <InputLabel value="Program Kerja" />
                        <select v-model="form.id_program_kerja" :disabled="isEditing" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:bg-gray-100">
                            <option value="" disabled>Pilih Program</option>
                            <option v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.nama_program }} ({{ prog.departemen?.nama_departemen }})</option>
                        </select>
                        <InputError :message="form.errors.id_program_kerja" />
                    </div>

                    <div>
                        <InputLabel value="Akun GL (COA)" />
                        <select v-model="form.id_akun_gl" :disabled="isEditing" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:bg-gray-100">
                            <option value="" disabled>Pilih Akun</option>
                            <option v-for="akun in akuns" :key="akun.id" :value="akun.id">{{ akun.kode_akun }} - {{ akun.nama_akun }}</option>
                        </select>
                        <InputError :message="form.errors.id_akun_gl" />
                    </div>

                    <div class="border-t pt-4 mt-4">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Delegasi Akses (Opsional)</h3>
                        <p class="text-xs text-gray-500 mb-3">Pilih departemen lain yang diizinkan menggunakan anggaran ini (selain {{ selectedProgramDept }}).</p>
                        <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border p-2 rounded bg-gray-50">
                            <div v-for="dept in departemens" :key="dept.id" class="flex items-center">
                                <input type="checkbox" :value="dept.id" v-model="form.delegasi_departemen" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">{{ dept.nama_departemen }}</span>
                            </div>
                        </div>
                        <InputError :message="form.errors.delegasi_departemen" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">{{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}</PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- MODAL: Konfirmasi Arsip -->
        <Modal :show="showArchiveModal" @close="showArchiveModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <ArchiveBoxArrowDownIcon class="w-5 h-5 text-amber-600"/>
                    </div>
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">Arsipkan Pos Anggaran</h2>
                        <p class="text-sm text-gray-500" v-if="archiveTarget">
                            <strong>{{ archiveTarget.akun_gl?.kode_akun }} - {{ archiveTarget.akun_gl?.nama_akun }}</strong>
                        </p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm text-amber-800">
                    <p class="font-semibold mb-1">Apa yang terjadi saat pos diarsipkan?</p>
                    <ul class="list-disc ml-4 space-y-1 text-xs text-amber-700">
                        <li>Pos ini <strong>tidak akan muncul</strong> di dropdown transaksi baru (pengajuan, budget)</li>
                        <li>Semua data historis (anggaran, transaksi) tetap <strong>utuh dan aman</strong></li>
                        <li>Dapat <strong>diaktifkan kembali</strong> kapan saja</li>
                    </ul>
                </div>

                <div>
                    <InputLabel value="Catatan Arsip (Opsional)" />
                    <input v-model="archiveForm.catatan_arsip" type="text"
                           placeholder="Contoh: Tidak digunakan di anggaran 2026"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-amber-500 focus:ring-amber-500" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showArchiveModal = false">Batal</SecondaryButton>
                    <button @click="submitArchive" :disabled="archiveForm.processing"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm rounded-md shadow-sm transition disabled:opacity-50">
                        Arsipkan Sekarang
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
