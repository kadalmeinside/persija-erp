<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputCurrency from '@/Components/InputCurrency.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { BanknotesIcon, PlusIcon, PencilSquareIcon, TrashIcon, CalculatorIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    form: Object,
    masterAkun: Array, // Full List (backup)
    masterPajak: Array,
    frontendErrors: Object,
    karyawan: Object // Needed for department info
});

// --- STATE ---
const showModal = ref(false);
const isEditingItem = ref(false);
const editingIndex = ref(null);
const modalForm = ref({ deskripsi_item: '', id_program: null, id_akun: null, id_tax_type: null, nominal_item: 0 });
const sisaSaldoModal = ref(0); 
const isLoadingSaldoModal = ref(false);
const modalErrors = ref({}); 

const filteredPrograms = ref([]);
const filteredAkun = ref([]);
const isLoadingPrograms = ref(false);
const isLoadingAkun = ref(false);

// --- UTILS ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

// --- DATA FETCHING ---
const fetchPrograms = async () => {
    if (!props.form.id_departemen) return;
    isLoadingPrograms.value = true;
    try {
        const response = await axios.get(route('admin.pengajuan.getProgramsByDepartemen'), {
            params: { 
                id_departemen: props.form.id_departemen,
                tgl_pengajuan: props.form.tgl_pengajuan 
            }
        });
        filteredPrograms.value = response.data;
    } catch (error) { console.error("Gagal ambil program:", error); } 
    finally { isLoadingPrograms.value = false; }
};

const fetchAccounts = async (preserveSelection = false) => {
    if (preserveSelection !== true) {
        modalForm.value.id_akun = null; sisaSaldoModal.value = 0; 
    }
    filteredAkun.value = [];
    if (!modalForm.value.id_program) return;
    isLoadingAkun.value = true;
    try {
        const response = await axios.get(route('admin.pengajuan.getAccountsByProgram'), {
            params: { 
                id_departemen: props.form.id_departemen, 
                id_program: modalForm.value.id_program,
                tgl_pengajuan: props.form.tgl_pengajuan 
            }
        });
        filteredAkun.value = response.data;
    } catch (error) { console.error(error); } 
    finally { isLoadingAkun.value = false; }
};

const onBudgetLineChange = async () => {
    const { id_akun, id_program } = modalForm.value;
    sisaSaldoModal.value = 0;
    modalErrors.value.nominal_item = '';
    
    if (!id_akun || !id_program) return;

    isLoadingSaldoModal.value = true;
    try {
        const response = await axios.get(route('admin.pengajuan.getBudgetBalance'), {
            params: { 
                id_departemen: props.form.id_departemen, 
                id_akun: id_akun, 
                id_program: id_program, 
                tgl_pengajuan: props.form.tgl_pengajuan 
            }
        });
        const saldoDariDB = response.data.sisa_saldo_db;
        
        const usedInCart = props.form.items.reduce((sum, item, idx) => {
            if (isEditingItem.value && idx === editingIndex.value) return sum;
            if (item.id_akun == id_akun && item.id_program == id_program) {
                return sum + (parseFloat(item.nominal_item) || 0);
            }
            return sum;
        }, 0);
        
        sisaSaldoModal.value = saldoDariDB - usedInCart;
    } catch (error) { console.error(error); } 
    finally { isLoadingSaldoModal.value = false; }
};

// --- ACTIONS ---
const openModalTambah = async () => {
    isEditingItem.value = false;
    editingIndex.value = null;
    modalForm.value = { deskripsi_item: '', id_program: null, id_akun: null, id_tax_type: null, nominal_item: 0 };
    filteredAkun.value = [];
    sisaSaldoModal.value = 0;
    modalErrors.value = {};
    
    // Ensure programs loaded
    if (filteredPrograms.value.length === 0) await fetchPrograms();
    
    showModal.value = true;
};

const editItem = async (index) => {
    isEditingItem.value = true;
    editingIndex.value = index;
    const item = props.form.items[index];
    modalForm.value = JSON.parse(JSON.stringify(item));
    
    if (filteredPrograms.value.length === 0) await fetchPrograms();
    await fetchAccounts(true); 
    await onBudgetLineChange(); 
    showModal.value = true;
};

const calculateGrossUp = () => {
    if (!modalForm.value.nominal_item || !modalForm.value.id_tax_type) return;
    const tax = props.masterPajak.find(t => t.id == modalForm.value.id_tax_type);
    if (!tax || tax.tipe !== 'PPh') return;
    const netAmount = parseFloat(modalForm.value.nominal_item);
    const rate = parseFloat(tax.rate) / 100;
    const grossAmount = netAmount / (1 - rate);
    modalForm.value.nominal_item = Math.ceil(grossAmount);
    alert(`Berhasil di-Gross Up!\n\nNetto Awal: ${formatCurrency(netAmount)}\nGross Baru: ${formatCurrency(modalForm.value.nominal_item)}`);
};

const simpanItem = () => {
    modalErrors.value = {};
    if (!modalForm.value.deskripsi_item) modalErrors.value.deskripsi_item = 'Wajib diisi.';
    if (!modalForm.value.id_program) modalErrors.value.id_program = 'Wajib dipilih.';
    if (!modalForm.value.id_akun) modalErrors.value.id_akun = 'Wajib dipilih.';
    if (!modalForm.value.nominal_item || modalForm.value.nominal_item <= 0) {
        modalErrors.value.nominal_item = 'Harus lebih besar dari 0.';
    }
    if (parseFloat(modalForm.value.nominal_item) > sisaSaldoModal.value) {
         modalErrors.value.nominal_item = `Melebihi saldo (Rp ${formatCurrency(sisaSaldoModal.value)})`;
    }
    
    if (Object.keys(modalErrors.value).length > 0) return;

    const itemData = JSON.parse(JSON.stringify(modalForm.value));
    if (isEditingItem.value && editingIndex.value !== null) {
        props.form.items[editingIndex.value] = itemData;
    } else {
        props.form.items.push(itemData);
    }
    showModal.value = false;
};

const removeItem = (index) => { props.form.items.splice(index, 1); };

// Helper to get names
const getProgramName = (id) => filteredPrograms.value.find(p => p.id == id)?.nama_program || '...';
const getAccountName = (id) => filteredAkun.value.find(a => a.id == id)?.nama_akun || props.masterAkun.find(a => a.id == id)?.nama_akun || '...';
const getTaxCode = (id) => props.masterPajak.find(p => p.id == id)?.kode_pajak;

// Expose openModalTambah for parent if needed via template refs
defineExpose({ openModalTambah });

</script>

<template>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
            <div>
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <BanknotesIcon class="w-5 h-5 mr-2 text-indigo-500"/>
                    {{ form.tipe_pengajuan === 'UangMuka' ? 'Rincian Alokasi Budget (Estimasi)' : 'Rincian Biaya Real (Faktur)' }}
                </h3>
                <p class="text-sm text-gray-500">
                    {{ form.tipe_pengajuan === 'UangMuka' ? 'Pilih pos anggaran yang akan dibebankan.' : 'Masukkan detail item sesuai bukti transaksi.' }}
                </p>
            </div>
            <SecondaryButton type="button" @click="openModalTambah">
                <PlusIcon class="w-4 h-4 mr-1" /> Tambah Item
            </SecondaryButton>
        </div>
        
        <InputError :message="frontendErrors['items'] || form.errors.items" class="mb-2" />

        <div class="block md:hidden space-y-4">
            <div v-if="form.items.length === 0" class="text-center text-gray-500 text-sm py-4 border-2 border-dashed border-gray-300 rounded-lg">
                Belum ada item rincian. <br> Klik "Tambah Item" untuk memulai.
            </div>
            <div v-for="(item, index) in form.items" :key="index" class="bg-white p-4 rounded-lg shadow border border-gray-200">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-mono text-gray-500 bg-gray-100 px-2 py-0.5 rounded">#{{ index + 1 }}</span>
                    <div class="flex space-x-2">
                        <button type="button" @click="editItem(index)" class="text-yellow-600 p-1 bg-yellow-50 rounded" title="Edit">
                            <PencilSquareIcon class="w-4 h-4"/>
                        </button>
                        <button type="button" @click="removeItem(index)" class="text-red-600 p-1 bg-red-50 rounded" title="Hapus">
                            <TrashIcon class="w-4 h-4"/>
                        </button>
                    </div>
                </div>
                
                <h4 class="font-medium text-gray-900 mb-2">{{ item.deskripsi_item }}</h4>
                
                <div class="space-y-1 text-xs text-gray-600 mb-3 border-t border-b py-2 border-dashed">
                    <div class="flex justify-between">
                        <span>Program:</span>
                        <span class="font-medium text-right ml-2">{{ getProgramName(item.id_program) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Akun:</span>
                        <span class="font-medium text-right ml-2">{{ getAccountName(item.id_akun) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pajak:</span>
                        <span class="font-medium text-right ml-2">{{ item.id_tax_type ? getTaxCode(item.id_tax_type) : '-' }}</span>
                    </div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-gray-500">Nominal</span>
                    <span class="text-lg font-bold text-indigo-700">{{ formatCurrency(item.nominal_item) }}</span>
                </div>
            </div>
            
             <!-- Mobile Total -->
             <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center border border-gray-200">
                <span class="font-bold text-gray-700 uppercase text-xs">Total Pengajuan</span>
                <span class="font-bold text-lg text-indigo-700">{{ formatCurrency(form.total_nominal_diajukan) }}</span>
            </div>
        </div>

        <div class="hidden md:block overflow-x-auto border rounded-lg shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase w-12">#</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program & Akun</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal (DPP)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pajak</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="form.items.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 text-sm italic">
                            Belum ada item rincian. Klik "Tambah Item" untuk memulai.
                        </td>
                    </tr>
                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm text-gray-500 text-center font-mono">{{ index + 1 }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ item.deskripsi_item }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <div class="text-indigo-700 font-semibold text-xs">{{ getProgramName(item.id_program) }}</div>
                            <div class="text-xs">{{ getAccountName(item.id_akun) }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                            {{ formatCurrency(item.nominal_item) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">
                            <div v-if="item.id_tax_type">
                                <span class="font-medium text-xs">{{ getTaxCode(item.id_tax_type) }}</span>
                            </div>
                            <div v-else class="text-xs text-gray-400">-</div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-indigo-700">
                             <!-- Simple estimate calculation for display, redundant with watcher logic but okay for now -->
                             {{ formatCurrency(item.nominal_item) }}
                        </td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <button type="button" @click="editItem(index)" class="text-yellow-600 hover:text-yellow-800 transition p-1 rounded hover:bg-yellow-100" title="Edit">
                                <PencilSquareIcon class="w-4 h-4"/>
                            </button>
                            <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-800 transition p-1 rounded hover:bg-red-100" title="Hapus">
                                <TrashIcon class="w-4 h-4"/>
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-100 font-bold text-gray-800">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-sm uppercase font-bold text-gray-600">Total Pengajuan</td>
                        <td class="px-4 py-3 text-right text-lg font-bold text-indigo-700">
                            {{ formatCurrency(form.total_nominal_diajukan) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- MODAL ITEM -->
    <Modal :show="showModal" @close="showModal = false" maxWidth="2xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditingItem ? 'Edit Item' : 'Tambah Item Baru' }}</h2>
            <div class="grid grid-cols-1 gap-6">
                <!-- Deskripsi -->
                <div>
                   <InputLabel value="Deskripsi Transaksi" />
                   <TextInput v-model="modalForm.deskripsi_item" class="w-full mt-1" placeholder="Contoh: Tiket Pesawat GA-404" />
                   <InputError :message="modalErrors.deskripsi_item" />
                </div>
                
                <!-- Program -->
                <div>
                    <InputLabel value="Program Kerja / Proyek" />
                    <select v-model="modalForm.id_program" items-center @change="fetchAccounts" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option :value="null">-- Pilih Program --</option>
                        <option v-for="p in filteredPrograms" :key="p.id" :value="p.id">{{ p.nama_program }}</option>
                    </select>
                    <div v-if="isLoadingPrograms" class="text-xs text-gray-500 mt-1">Memuat program...</div>
                    <InputError :message="modalErrors.id_program" />
                </div>

                <!-- Akun GL -->
                <div>
                    <InputLabel value="Akun Anggaran (COA)" />
                    <select v-model="modalForm.id_akun" @change="onBudgetLineChange" :disabled="!modalForm.id_program" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm disabled:bg-gray-100">
                        <option :value="null">-- Pilih Akun --</option>
                        <option v-for="a in filteredAkun" :key="a.id" :value="a.id">{{ a.kode_akun }} - {{ a.nama_akun }}</option>
                    </select>
                     <div v-if="isLoadingAkun" class="text-xs text-gray-500 mt-1">Memuat akun...</div>
                    <InputError :message="modalErrors.id_akun" />
                    
                    <!-- SISA SALDO INFO -->
                    <div v-if="modalForm.id_akun" class="mt-2 p-3 bg-blue-50 rounded text-sm flex justify-between items-center">
                        <span class="text-blue-800">Sisa Saldo Anggaran:</span>
                        <span class="font-bold text-blue-900" :class="{'text-red-600': sisaSaldoModal <= 0}">
                             {{ isLoadingSaldoModal ? 'Checking...' : formatCurrency(sisaSaldoModal) }}
                        </span>
                    </div>
                </div>

                <!-- Nominal -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Nominal (DPP)" />
                        <InputCurrency v-model="modalForm.nominal_item" class="w-full mt-1" />
                        <InputError :message="modalErrors.nominal_item" />
                    </div>
                    <div>
                        <InputLabel value="Pajak (Opsional)" />
                        <select v-model="modalForm.id_tax_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option :value="null">Tidak Ada</option>
                            <option v-for="t in masterPajak" :key="t.id" :value="t.id">
                                {{ t.kode_pajak }} ({{ t.tipe }} {{ t.rate }}%)
                            </option>
                        </select>
                        <button v-if="modalForm.id_tax_type && masterPajak.find(t=>t.id==modalForm.id_tax_type)?.tipe === 'PPh'" 
                            type="button" 
                            @click="calculateGrossUp" 
                            class="text-xs text-indigo-600 hover:underline mt-1 flex items-center">
                            <CalculatorIcon class="w-3 h-3 mr-1"/> Hitung Gross Up
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton type="button" @click="showModal = false">Batal</SecondaryButton>
                <PrimaryButton type="button" @click="simpanItem">Simpan Item</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
