<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputCurrency from '@/Components/InputCurrency.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { 
    DocumentTextIcon, BanknotesIcon, PlusIcon, TrashIcon, 
    ArrowLeftIcon, ReceiptPercentIcon, ExclamationTriangleIcon,
    CheckIcon, PaperClipIcon, PencilSquareIcon, XMarkIcon
} from '@heroicons/vue/24/solid';
import axios from 'axios'; // Jangan lupa import axios

const props = defineProps({
    pengajuan: Object, 
    penerima: Object,  
    masterProgram: Array,
    masterAkun: Array
});

const page = usePage();
const currentUser = page.props.auth.user;

const isAuthorized = computed(() => {
    if (currentUser.roles.some(r => r.name === 'Super Admin')) return true;
    return currentUser.karyawan && currentUser.karyawan.id === props.penerima.id;
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const defaultProgram = props.pengajuan.detail[0]?.id_program;
const defaultAkun = props.pengajuan.detail[0]?.id_akun;

const form = useForm({
    tgl_laporan: new Date().toISOString().split('T')[0],
    items: [],
    bukti_pengembalian: null,
});

onMounted(() => {
    // Auto-fill (Pre-filled) dari pengajuan awal
    if (props.pengajuan && props.pengajuan.detail && props.pengajuan.detail.length > 0) {
        form.items = props.pengajuan.detail.map(d => ({
            deskripsi_bon: d.deskripsi_item,
            nominal_bon: d.nominal_item, // Isi otomatis nominal awal (bisa diedit user)
            id_program: d.id_program,
            id_akun: d.id_akun,
            file_bukti: null
        }));
    }
});

const showModal = ref(false);
const isEditing = ref(false);
const editingIndex = ref(null);
const modalForm = ref({
    deskripsi_bon: '',
    nominal_bon: 0,
    id_program: defaultProgram,
    id_akun: defaultAkun,
    file_bukti: null
});
const modalErrors = ref({});

// --- LOGIC CEK SALDO (BARU) ---
const sisaSaldoAnggaran = ref(0);
const isLoadingSaldo = ref(false);

const checkSaldo = async () => {
    // Reset
    sisaSaldoAnggaran.value = 0;
    modalErrors.value.nominal_bon = ''; // Clear error lama

    if (!modalForm.value.id_akun || !modalForm.value.id_program) return;

    isLoadingSaldo.value = true;
    try {
        // Kita bisa reuse endpoint getBudgetBalance milik PengajuanController
        // Karena logikanya sama: Cek sisa budget untuk Dept, Program, Akun pada Tanggal tertentu.
        const response = await axios.get(route('admin.pengajuan.getBudgetBalance'), {
            params: { 
                id_departemen: props.pengajuan.id_departemen, 
                id_akun: modalForm.value.id_akun, 
                id_program: modalForm.value.id_program, 
                tgl_pengajuan: form.tgl_laporan // Gunakan tanggal laporan realisasi
            }
        });
        
        // Note: Sisa saldo ini adalah saldo di database. 
        // Belum ditambah dengan pengembalian (reversal) uang muka ini sendiri.
        // Jadi ini adalah estimasi konservatif (lebih aman).
        sisaSaldoAnggaran.value = response.data.sisa_saldo_db;
    } catch (error) {
        console.error("Gagal cek saldo:", error);
    } finally {
        isLoadingSaldo.value = false;
    }
};
// ------------------------------

const totalUangMuka = computed(() => parseFloat(props.pengajuan.total_nominal_diajukan));
const totalRealisasi = computed(() => form.items.reduce((sum, item) => sum + (parseInt(item.nominal_bon) || 0), 0));
const selisih = computed(() => totalUangMuka.value - totalRealisasi.value);

const openModal = () => {
    isEditing.value = false;
    editingIndex.value = null;
    const lastItem = form.items.length > 0 ? form.items[form.items.length - 1] : null;
    
    modalForm.value = {
        deskripsi_bon: '',
        nominal_bon: 0,
        id_program: lastItem?.id_program || defaultProgram,
        id_akun: lastItem?.id_akun || defaultAkun,
        file_bukti: null
    };
    
    modalErrors.value = {};
    showModal.value = true;
    
    // Cek saldo awal saat modal dibuka (jika default value ada)
    checkSaldo();
};

const editItem = (index) => {
    isEditing.value = true;
    editingIndex.value = index;
    modalForm.value = { ...form.items[index] };
    modalErrors.value = {};
    showModal.value = true;
    
    // Cek saldo untuk item yang diedit
    checkSaldo();
};

const saveItem = () => {
    modalErrors.value = {};
    
    if (!modalForm.value.deskripsi_bon) modalErrors.value.deskripsi_bon = "Wajib diisi.";
    if (!modalForm.value.nominal_bon || modalForm.value.nominal_bon <= 0) modalErrors.value.nominal_bon = "Harus lebih dari 0.";
    if (!modalForm.value.id_program) modalErrors.value.id_program = "Wajib dipilih.";
    if (!modalForm.value.id_akun) modalErrors.value.id_akun = "Wajib dipilih.";

    // Validasi Saldo (Opsional: Bisa dibuat warning saja atau strict)
    // Kita buat strict agar aman
    if (parseFloat(modalForm.value.nominal_bon) > sisaSaldoAnggaran.value) {
         modalErrors.value.nominal_bon = `Melebihi sisa anggaran tersedia (Rp ${formatCurrency(sisaSaldoAnggaran.value)})`;
         return;
    }
    
    // Validasi file_bukti wajib diupload
    if (!modalForm.value.file_bukti) {
         modalErrors.value.file_bukti = "File bukti (bon/struk) wajib diunggah.";
    }

    if (Object.keys(modalErrors.value).length > 0) return;

    if (isEditing.value) {
        form.items[editingIndex.value] = { ...modalForm.value };
    } else {
        form.items.push({ ...modalForm.value });
    }
    showModal.value = false;
};

const deleteItem = (index) => {
    if(confirm('Hapus baris ini?')) {
        form.items.splice(index, 1);
    }
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) modalForm.value.file_bukti = file;
};

const submit = () => {
    if (form.items.length === 0) {
        alert("Mohon tambahkan minimal satu rincian penggunaan dana."); return;
    }
    
    const missingProofIndex = form.items.findIndex(item => !item.file_bukti);
    if (missingProofIndex !== -1) {
        alert(`Rincian baris ke-${missingProofIndex + 1} belum melampirkan bukti bon/struk.`);
        return;
    }

    if (selisih.value > 0 && !form.bukti_pengembalian) {
        alert("Terdapat sisa uang. Mohon lampirkan bukti transfer pengembalian."); return;
    }
    form.post(route('admin.settlement.store', props.pengajuan.id), {
        preserveScroll: true,
        onSuccess: () => alert('Laporan Pertanggungjawaban berhasil disimpan!'),
    });
};
</script>

<template>
    <Head title="Laporan Pertanggungjawaban" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.pengajuan.show', pengajuan.id)" class="p-2 rounded-full hover:bg-gray-100 transition">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600"/>
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan Pertanggungjawaban</h2>
                    <p class="text-xs text-gray-500 font-mono mt-0.5">Ref: {{ pengajuan.nomor_pengajuan }}</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- UNAUTHORIZED ALERT -->
                <div v-if="!isAuthorized" class="bg-red-50 border-l-4 border-red-500 p-4 rounded shadow">
                    <div class="flex">
                        <div class="flex-shrink-0"><ExclamationTriangleIcon class="h-5 w-5 text-red-400" /></div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                Anda tidak memiliki hak akses. Laporan ini milik <b>{{ penerima.nama_lengkap }}</b>.
                            </p>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <!-- CARD 1: INFO UANG MUKA -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-6 bg-gradient-to-r from-purple-50 via-white to-white flex flex-col md:flex-row justify-between items-center gap-4">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-purple-100 text-purple-600 rounded-full">
                                    <DocumentTextIcon class="w-8 h-8"/>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Keperluan Uang Muka</p>
                                    <p class="text-lg font-bold text-gray-900">{{ pengajuan.judul_pengajuan }}</p>
                                    <p class="text-sm text-gray-500">Penerima: {{ penerima.nama_lengkap }}</p>
                                </div>
                            </div>
                            <div class="text-right bg-white p-4 rounded border border-purple-100 shadow-sm">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Diterima</p>
                                <p class="text-3xl font-bold text-purple-700">{{ formatCurrency(totalUangMuka) }}</p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit">
                        
                        <!-- CARD 2: DAFTAR REALISASI -->
                        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mt-6">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold text-gray-800 flex items-center text-lg">
                                        <ReceiptPercentIcon class="w-5 h-5 mr-2 text-indigo-500"/> Realisasi Penggunaan
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Masukkan rincian belanja sesuai bukti struk/bon asli.</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-right mr-4 hidden md:block">
                                        <span class="text-xs text-gray-500 uppercase font-bold">Total Terpakai</span>
                                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(totalRealisasi) }}</p>
                                    </div>
                                    <SecondaryButton type="button" @click="openModal">
                                        <PlusIcon class="w-4 h-4 mr-2"/> Tambah Item
                                    </SecondaryButton>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <div class="w-full md:w-1/4 mb-6">
                                    <InputLabel value="Tanggal Laporan" />
                                    <TextInput type="date" v-model="form.tgl_laporan" class="w-full mt-1" />
                                    <InputError :message="form.errors.tgl_laporan" />
                                </div>

                                <!-- TABEL ITEM -->
                                <div class="overflow-x-auto border rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-bold">
                                            <tr>
                                                <th class="px-4 py-3 w-10 text-center">#</th>
                                                <th class="px-4 py-3">Deskripsi</th>
                                                <th class="px-4 py-3">Pos Anggaran</th>
                                                <th class="px-4 py-3 text-center">Bukti</th>
                                                <th class="px-4 py-3 text-right">Nominal</th>
                                                <th class="px-4 py-3 text-center w-24">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-if="form.items.length === 0">
                                                <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">
                                                    Belum ada data realisasi. Silakan klik "Tambah Item".
                                                </td>
                                            </tr>
                                            <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50">
                                                <td class="px-4 py-3 text-center text-gray-500">{{ index + 1 }}</td>
                                                <td class="px-4 py-3 font-medium text-gray-900">{{ item.deskripsi_bon }}</td>
                                                <td class="px-4 py-3 text-xs text-gray-500">
                                                    <div class="text-indigo-600 font-semibold">
                                                        {{ masterProgram.find(p => p.id == item.id_program)?.nama_program }}
                                                    </div>
                                                    <div>
                                                        {{ masterAkun.find(a => a.id == item.id_akun)?.kode_akun }} - {{ masterAkun.find(a => a.id == item.id_akun)?.nama_akun }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div v-if="item.file_bukti" class="flex items-center justify-center text-green-600 text-xs" title="File Terlampir">
                                                        <PaperClipIcon class="w-4 h-4 mr-1"/> {{ item.file_bukti.name.substring(0, 10) }}...
                                                    </div>
                                                    <span v-else class="text-gray-400 text-xs italic">Tidak ada</span>
                                                </td>
                                                <td class="px-4 py-3 text-right font-mono font-bold text-gray-800">
                                                    {{ formatCurrency(item.nominal_bon) }}
                                                </td>
                                                <td class="px-4 py-3 text-center space-x-2">
                                                    <button type="button" @click="editItem(index)" class="text-yellow-600 hover:text-yellow-700">
                                                        <PencilSquareIcon class="w-4 h-4"/>
                                                    </button>
                                                    <button type="button" @click="deleteItem(index)" class="text-red-500 hover:text-red-700">
                                                        <TrashIcon class="w-4 h-4"/>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50 font-bold text-gray-800">
                                            <tr>
                                                <td colspan="4" class="px-4 py-3 text-right uppercase text-xs tracking-wider">Total Realisasi</td>
                                                <td class="px-4 py-3 text-right text-indigo-700 text-lg">{{ formatCurrency(totalRealisasi) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 3: ANALISA SELISIH (SAMA SEPERTI SEBELUMNYA) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm h-fit">
                                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Hasil Perhitungan</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Uang Muka Awal</span>
                                        <span class="font-medium">{{ formatCurrency(totalUangMuka) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Total Penggunaan</span>
                                        <span class="font-bold text-red-600">(-) {{ formatCurrency(totalRealisasi) }}</span>
                                    </div>
                                    <div class="border-t border-dashed pt-3 flex justify-between items-center bg-gray-50 p-2 rounded">
                                        <span class="font-bold text-gray-700">Selisih Akhir</span>
                                        <span class="text-xl font-bold" :class="selisih >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ formatCurrency(Math.abs(selisih)) }}
                                        </span>
                                    </div>
                                    <div class="mt-2 p-3 rounded-lg text-sm font-medium text-center border"
                                        :class="selisih > 0 ? 'bg-green-50 border-green-200 text-green-800' : (selisih < 0 ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 'bg-gray-100 border-gray-200 text-gray-600')">
                                        <span v-if="selisih > 0">
                                            <span class="font-bold block mb-1">SISA UANG (LEBIH)</span>
                                            Wajib dikembalikan ke rekening kantor.
                                        </span>
                                        <span v-else-if="selisih < 0">
                                            <span class="font-bold block mb-1">KURANG BAYAR (NOMBOK)</span>
                                            Kantor akan mengganti (reimburse) kekurangan ini.
                                        </span>
                                        <span v-else>
                                            <span class="font-bold block mb-1">BALANCE (PAS)</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-center">
                                <div v-if="selisih > 0">
                                    <h3 class="font-bold text-gray-800 mb-2 flex items-center">
                                        <BanknotesIcon class="w-5 h-5 mr-2 text-green-600"/> Bukti Pengembalian Dana
                                    </h3>
                                    <p class="text-sm text-gray-500 mb-4">Silakan transfer sisa uang <b>{{ formatCurrency(selisih) }}</b> ke rekening kantor dan upload buktinya.</p>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition cursor-pointer relative group">
                                        <input type="file" @input="form.bukti_pengembalian = $event.target.files[0]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*,application/pdf" />
                                        <div v-if="!form.bukti_pengembalian" class="flex flex-col items-center">
                                            <div class="p-2 bg-gray-100 rounded-full mb-2 group-hover:bg-indigo-100 transition"><PlusIcon class="w-6 h-6 text-gray-400 group-hover:text-indigo-500"/></div>
                                            <p class="text-sm text-gray-600 font-medium">Klik untuk upload bukti</p>
                                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF (Max 2MB)</p>
                                        </div>
                                        <div v-else class="flex flex-col items-center">
                                            <div class="p-2 bg-green-100 rounded-full mb-2"><CheckIcon class="w-6 h-6 text-green-600"/></div>
                                            <p class="text-sm text-green-700 font-bold truncate max-w-[200px]">{{ form.bukti_pengembalian.name }}</p>
                                            <p class="text-xs text-gray-500 mt-1">Klik untuk ganti file</p>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.bukti_pengembalian" class="mt-2" />
                                </div>
                                <div v-else-if="selisih < 0" class="text-center p-4">
                                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4"><ExclamationTriangleIcon class="w-8 h-8 text-yellow-600"/></div>
                                    <h4 class="font-bold text-gray-900 mb-2">Pengajuan Reimburse Otomatis</h4>
                                    <p class="text-sm text-gray-600">Selisih sebesar <b class="text-gray-800">{{ formatCurrency(Math.abs(selisih)) }}</b> akan dicatat sebagai hutang perusahaan kepada Anda.</p>
                                </div>
                                <div v-else class="text-center p-4">
                                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4"><CheckIcon class="w-8 h-8 text-green-600"/></div>
                                    <h4 class="font-bold text-gray-900 mb-2">Laporan Selesai</h4>
                                    <p class="text-sm text-gray-600">Laporan penggunaan dana pas.</p>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="mt-8 flex justify-end pt-6 border-t border-gray-200">
                            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing" class="px-6 py-3 text-base shadow-lg">
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Simpan & Finalisasi Laporan</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- MODAL TAMBAH/EDIT ITEM (DENGAN CEK SALDO) -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Rincian' : 'Tambah Rincian Baru' }}</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-6 h-6"/></button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Keperluan / Deskripsi" />
                        <TextInput v-model="modalForm.deskripsi_bon" class="w-full mt-1" placeholder="Contoh: Makan siang tim di Resto X" />
                        <InputError :message="modalErrors.deskripsi_bon"/>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Program Kerja" />
                            <select v-model="modalForm.id_program" @change="checkSaldo" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <option :value="null">Pilih Program...</option>
                                <option v-for="p in masterProgram" :key="p.id" :value="p.id">{{ p.nama_program }}</option>
                            </select>
                            <InputError :message="modalErrors.id_program"/>
                        </div>
                        <div>
                            <InputLabel value="Akun Anggaran (COA)" />
                            <select v-model="modalForm.id_akun" @change="checkSaldo" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <option :value="null">Pilih Akun...</option>
                                <option v-for="a in masterAkun" :key="a.id" :value="a.id">{{ a.kode_akun }} - {{ a.nama_akun }}</option>
                            </select>
                            <InputError :message="modalErrors.id_akun"/>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Nominal Real (Sesuai Struk)" />
                        <InputCurrency v-model="modalForm.nominal_bon" class="mt-1" placeholder="0" />
                        
                        <!-- INFO SALDO (TAMBAHAN BARU) -->
                        <div v-if="modalForm.id_akun && modalForm.id_program" class="text-xs mt-1 flex justify-between">
                            <span class="text-gray-500">Sisa Budget Tersedia:</span>
                            <span :class="sisaSaldoAnggaran >= 0 ? 'text-green-600 font-bold' : 'text-red-600 font-bold'">
                                {{ isLoadingSaldo ? 'Cek...' : formatCurrency(sisaSaldoAnggaran) }}
                            </span>
                        </div>
                        
                        <InputError :message="modalErrors.nominal_bon"/>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <InputLabel value="Bukti Struk / Bon (Foto/PDF)" />
                        <div class="mt-1 flex items-center justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition cursor-pointer relative group">
                            <input type="file" @change="handleFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*,application/pdf"/>
                            <div class="space-y-1 text-center">
                                <PaperClipIcon class="mx-auto h-8 w-8 text-gray-400" :class="{'text-indigo-500': modalForm.file_bukti}"/>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <span v-if="modalForm.file_bukti" class="font-bold text-indigo-600 truncate max-w-[200px]">{{ modalForm.file_bukti.name }}</span>
                                    <span v-else class="font-medium text-indigo-600 hover:text-indigo-500">Upload file</span>
                                </div>
                                <p v-if="!modalForm.file_bukti" class="text-xs text-gray-500">PNG, JPG, PDF up to 2MB</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="saveItem">Simpan Rincian</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>