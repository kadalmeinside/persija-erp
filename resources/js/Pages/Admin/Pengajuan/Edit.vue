<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue'; 
import VendorModal from '@/Components/VendorModal.vue';
import EmployeeBankModal from '@/Components/EmployeeBankModal.vue';
import { useClientValidation } from '@/Composables/useClientValidation';
import FilePreviewModal from '@/Components/FilePreviewModal.vue';
import InputCurrency from '@/Components/InputCurrency.vue';

import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { 
    UserCircleIcon, PlusIcon, TrashIcon, XMarkIcon, 
    MagnifyingGlassIcon, ChevronUpDownIcon, CheckIcon, EyeIcon, 
    BuildingStorefrontIcon, PencilSquareIcon, CreditCardIcon, 
    BanknotesIcon, DocumentTextIcon, UserGroupIcon, PaperClipIcon,
    DocumentDuplicateIcon
} from '@heroicons/vue/24/solid';
import axios from 'axios'; 

const props = defineProps({
    pengajuan: Object,      
    masterAkun: Array,     
    masterProgram: Array,  
    masterPajak: Array,
    karyawan: Object,
    masterVendor: Array,
    masterKaryawan: Array
});

// --- UTILS ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

// --- INITIAL STATE LOGIC ---
const initialSubTipe = () => {
    if (props.pengajuan.tipe_pengajuan === 'UangMuka') return 'Karyawan';
    if (props.pengajuan.id_karyawan_penerima) return 'Karyawan';
    return 'Vendor';
};

// --- MAIN FORM STATE ---
const form = useForm({
    _method: 'PUT', 
    judul_pengajuan: props.pengajuan.judul_pengajuan, 
    tgl_pengajuan: props.pengajuan.tgl_pengajuan,
    id_pengaju: props.pengajuan.id_pengaju,
    id_departemen: props.pengajuan.id_departemen,
    tipe_pengajuan: props.pengajuan.tipe_pengajuan,
    metode_pembayaran: props.pengajuan.metode_pembayaran, 
    
    sub_tipe_penerima: initialSubTipe(),

    id_vendor_penerima: props.pengajuan.id_vendor_penerima, 
    id_karyawan_penerima: props.pengajuan.id_karyawan_penerima, 

    bank_tujuan: props.pengajuan.bank_tujuan,
    no_rek_tujuan: props.pengajuan.no_rek_tujuan,
    atas_nama_tujuan: props.pengajuan.atas_nama_tujuan,

    attachment: null, 
    catatan_header: props.pengajuan.catatan_header,
    total_nominal_diajukan: parseFloat(props.pengajuan.total_nominal_diajukan),
    
    items: props.pengajuan.detail.map(item => ({
        deskripsi_item: item.deskripsi_item,
        id_program: item.id_program,
        id_akun: item.id_akun,
        id_pajak: item.id_pajak,
        nominal_item: parseFloat(item.nominal_item)
    }))
});

// Watcher untuk Reset Logic saat Switch Vendor/Karyawan
watch(() => form.sub_tipe_penerima, (newVal) => {
    if (newVal === 'Karyawan') {
        form.id_vendor_penerima = null;
        
        if (!form.id_karyawan_penerima) {
             // Default ke user login
             form.id_karyawan_penerima = props.karyawan?.id;
        }
        
        // Coba populate bank dari ID (jika ada)
        const k = props.masterKaryawan.find(item => item.id == form.id_karyawan_penerima);
        if (k && k.primary_bank) {
             form.bank_tujuan = k.primary_bank.nama_bank;
             form.no_rek_tujuan = k.primary_bank.nomor_rekening;
             form.atas_nama_tujuan = k.primary_bank.atas_nama_rekening;
        } else {
             // Reset jika bank tidak ada, agar tidak nyangkut data vendor
             form.bank_tujuan = ''; form.no_rek_tujuan = ''; form.atas_nama_tujuan = '';
        }
    } else {
        form.id_karyawan_penerima = null;
        
        // Coba populate bank vendor jika ID vendor masih tersimpan (jarang, biasanya null)
        const v = props.masterVendor.find(item => item.id == form.id_vendor_penerima);
         if (v && v.primary_bank) {
             form.bank_tujuan = v.primary_bank.nama_bank;
             form.no_rek_tujuan = v.primary_bank.nomor_rekening;
             form.atas_nama_tujuan = v.primary_bank.atas_nama_rekening;
        } else {
             form.bank_tujuan = ''; form.no_rek_tujuan = ''; form.atas_nama_tujuan = '';
        }
    }
}, { immediate: true });

// --- DATA FETCHING ---
const filteredPrograms = ref([]); 
const filteredAkun = ref([]); 
const isLoadingPrograms = ref(false);
const isLoadingAkun = ref(false);

const fetchPrograms = async () => {
    if (!form.id_departemen) return;
    isLoadingPrograms.value = true;
    try {
        const response = await axios.get(route('admin.pengajuan.getProgramsByDepartemen'), {
            params: { id_departemen: form.id_departemen }
        });
        filteredPrograms.value = response.data;
    } catch (error) { console.error(error); } finally { isLoadingPrograms.value = false; }
};

const fetchAccounts = async (preserveSelection = false) => {
    if (preserveSelection !== true) {
        modalForm.value.id_akun = null; 
        sisaSaldoModal.value = 0; 
    }
    filteredAkun.value = [];
    if (!modalForm.value.id_program) return;

    isLoadingAkun.value = true;
    try {
        const response = await axios.get(route('admin.pengajuan.getAccountsByProgram'), {
            params: { id_departemen: form.id_departemen, id_program: modalForm.value.id_program }
        });
        filteredAkun.value = response.data;
    } catch (error) { console.error(error); } 
    finally { isLoadingAkun.value = false; }
};

// --- VENDOR LOGIC ---
const showSelectVendorModal = ref(false);
const searchVendorQuery = ref('');
const showVendorModal = ref(false); 

const filteredVendors = computed(() => {
    if (!searchVendorQuery.value) return props.masterVendor;
    const lower = searchVendorQuery.value.toLowerCase();
    return props.masterVendor.filter(v => v.nama_vendor.toLowerCase().includes(lower));
});

const selectedVendor = computed(() => {
    return props.masterVendor.find(v => v.id == form.id_vendor_penerima);
});

watch(selectedVendor, (newVal) => {
    if (newVal && newVal.primary_bank) {
        form.bank_tujuan = newVal.primary_bank.nama_bank;
        form.no_rek_tujuan = newVal.primary_bank.nomor_rekening;
        form.atas_nama_tujuan = newVal.primary_bank.atas_nama_rekening;
    }
});

const handleVendorCreated = (newVendor) => {
    props.masterVendor.push(newVendor);     
    form.id_vendor_penerima = newVendor.id; 
    showSelectVendorModal.value = false; showVendorModal.value = false;          
};

// --- KARYAWAN LOGIC ---
const showEmployeeBankModal = ref(false);
const selectedEmployee = computed(() => (props.masterKaryawan || []).find(k => k.id == form.id_karyawan_penerima));
const selectedEmployeeBank = computed(() => selectedEmployee.value ? selectedEmployee.value.primary_bank : null);

watch(selectedEmployeeBank, (newVal) => {
    if (newVal) {
        form.bank_tujuan = newVal.nama_bank;
        form.no_rek_tujuan = newVal.nomor_rekening;
        form.atas_nama_tujuan = newVal.atas_nama_rekening;
    }
});

const handleEmployeeBankCreated = (newBank) => {
    const empIndex = props.masterKaryawan.findIndex(k => k.id == form.id_karyawan_penerima);
    if (empIndex !== -1) props.masterKaryawan[empIndex].primary_bank = newBank;
    showEmployeeBankModal.value = false;
};

// --- ITEM / CART LOGIC ---
const showModal = ref(false);
const isEditingItem = ref(false);
const editingIndex = ref(null);
const modalForm = ref({ deskripsi_item: '', id_program: null, id_akun: null, id_pajak: null, nominal_item: 0 });
const sisaSaldoModal = ref(0); 
const isLoadingSaldoModal = ref(false);
const modalErrors = ref({}); 

const openModalTambah = async () => {
    isEditingItem.value = false;
    editingIndex.value = null;
    modalForm.value = { deskripsi_item: '', id_program: null, id_akun: null, id_pajak: null, nominal_item: 0 };
    filteredAkun.value = []; sisaSaldoModal.value = 0; modalErrors.value = {};
    showModal.value = true;
    if (filteredPrograms.value.length === 0) await fetchPrograms(); 
};

const editItem = async (index) => {
    isEditingItem.value = true;
    editingIndex.value = index;
    const item = form.items[index];
    modalForm.value = JSON.parse(JSON.stringify(item));
    
    showModal.value = true;
    await fetchPrograms();
    await fetchAccounts(true); 
    await onBudgetLineChange(); 
};

const onBudgetLineChange = async () => {
    const { id_akun, id_program } = modalForm.value;
    sisaSaldoModal.value = 0; modalErrors.value.nominal_item = '';
    if (!id_akun || !id_program) return;

    isLoadingSaldoModal.value = true;
    try {
        const response = await axios.get(route('admin.pengajuan.getBudgetBalance'), {
            params: { id_departemen: form.id_departemen, id_akun: id_akun, id_program: id_program, tgl_pengajuan: form.tgl_pengajuan }
        });
        sisaSaldoModal.value = response.data.sisa_saldo_db;
    } catch (error) { console.error(error); } 
    finally { isLoadingSaldoModal.value = false; }
};

const simpanItem = () => {
    modalErrors.value = {};
    if (!modalForm.value.deskripsi_item) modalErrors.value.deskripsi_item = 'Wajib diisi.';
    if (!modalForm.value.id_program) modalErrors.value.id_program = 'Wajib dipilih.';
    if (!modalForm.value.id_akun) modalErrors.value.id_akun = 'Wajib dipilih.';
    if (!modalForm.value.nominal_item || modalForm.value.nominal_item <= 0) modalErrors.value.nominal_item = 'Harus > 0.';
    if (Object.keys(modalErrors.value).length > 0) return;

    const itemData = JSON.parse(JSON.stringify(modalForm.value));
    if (isEditingItem.value && editingIndex.value !== null) form.items[editingIndex.value] = itemData;
    else form.items.push(itemData);
    showModal.value = false;
};

const removeItem = (index) => { form.items.splice(index, 1); };

watch(() => form.items, (newItems) => {
    form.total_nominal_diajukan = newItems.reduce((acc, item) => acc + (parseFloat(item.nominal_item) || 0), 0);
}, { deep: true });

// Handle onMounted combined at the bottom
// onMounted(() => {
//     if(form.id_departemen) fetchPrograms();
// });

// --- SUBMIT ---
const { clientErrors: frontendErrors, clearAllClientErrors, hasClientErrors } = useClientValidation();

const submit = () => {
    form.clearErrors();
    clearAllClientErrors();
    const errors = {};

    if (!form.judul_pengajuan) errors['judul_pengajuan'] = 'Judul wajib diisi.';
    if (!form.tgl_pengajuan) errors['tgl_pengajuan'] = 'Tanggal wajib diisi.';
    if (form.items.length === 0) errors['items'] = 'Minimal 1 item rincian harus ditambahkan.';

    if (form.metode_pembayaran === 'Transfer') {
        const isVendorTarget = (form.tipe_pengajuan === 'Langsung' && form.sub_tipe_penerima === 'Vendor');
        if (isVendorTarget) {
            if (!form.id_vendor_penerima) errors['id_vendor_penerima'] = 'Vendor wajib dipilih.';
            else if (!form.bank_tujuan) errors['bank_tujuan'] = 'Vendor terpilih tidak memiliki data bank.';
        } else {
            if (!form.id_karyawan_penerima) errors['id_karyawan_penerima'] = 'Karyawan wajib dipilih.';
            else if (!form.bank_tujuan) errors['bank_tujuan'] = 'Karyawan terpilih belum memiliki rekening bank.';
        }
    } else {
        form.bank_tujuan = ''; form.no_rek_tujuan = ''; form.atas_nama_tujuan = '';
    }

    Object.assign(frontendErrors.value, errors);
    if (hasClientErrors(errors)) { window.scrollTo({ top: 0, behavior: 'smooth' }); return; }

    form.post(route('admin.pengajuan.update', props.pengajuan.id), {
        preserveScroll: true,
        onError: (errors) => { console.error('Backend Error', errors); },
    });
};

// File Preview
const showFilePreview = ref(false);
const previewUrl = ref(''); const previewType = ref(''); const previewName = ref('');
const activeAttachmentUrl = computed(() => previewUrl.value || (props.pengajuan.attachment_path ? `/storage/${props.pengajuan.attachment_path}` : ''));
const activeAttachmentName = computed(() => previewName.value || (props.pengajuan.attachment_path ? props.pengajuan.attachment_path.split('/').pop() : 'Lampiran'));
const activeAttachmentType = computed(() => previewType.value || (props.pengajuan.attachment_path?.toLowerCase().endsWith('.pdf') ? 'pdf' : 'image'));

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.attachment = file;
        previewUrl.value = URL.createObjectURL(file);
        previewType.value = file.type.includes('pdf') ? 'pdf' : 'image';
        previewName.value = file.name;
    }
};

const handlePaste = (e) => {
    // Hanya proses paste jika form membutuhkan lampiran (Bukan UangMuka tanpa lampiran, dsb)
    // Tipe Langsung / TaxPayment biasanya butuh lampiran
    if (form.tipe_pengajuan !== 'Langsung' && form.tipe_pengajuan !== 'TaxPayment' && form.tipe_pengajuan !== 'PettyCash') return;

    const items = e.clipboardData?.items;
    if (!items) return;

    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            const file = items[i].getAsFile();
            if (file) {
                const ext = file.type.split('/')[1] || 'png';
                const newFile = new File([file], `paste_${Date.now()}.${ext}`, { type: file.type });
                form.attachment = newFile;
                previewUrl.value = URL.createObjectURL(newFile);
                previewType.value = 'image';
                previewName.value = newFile.name;
                
                // Update file input visually
                const fileInput = document.querySelector('input[type="file"]');
                if (fileInput) {
                    const dt = new DataTransfer();
                    dt.items.add(newFile);
                    fileInput.files = dt.files;
                }

                e.preventDefault();
                break;
            }
        }
    }
};

onMounted(() => {
    window.addEventListener('paste', handlePaste);
    if(form.id_departemen) fetchPrograms();
});

onUnmounted(() => {
    window.removeEventListener('paste', handlePaste);
});
</script>

<template>
    <Head title="Edit Pengajuan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-lg md:text-xl text-gray-800 leading-tight">Edit Pengajuan</h2>
                        <span class="text-xs px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-sm font-bold border border-yellow-200">Mode Edit</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 font-mono">#{{ pengajuan.nomor_pengajuan }}</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow rounded-lg p-6">
                    
                    <!-- Alert -->
                    <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md border border-red-200 flex items-start">
                        <XMarkIcon class="w-5 h-5 mr-2 mt-0.5"/> {{ $page.props.flash.error }}
                    </div>
                    <div v-if="Object.keys(form.errors).length > 0" class="mb-4 p-4 bg-red-50 text-red-700 rounded text-sm">
                        <ul class="list-disc ml-5"><li v-for="(err, k) in form.errors" :key="k">{{ err }}</li></ul>
                    </div>
                    
                    <form @submit.prevent="submit">
                        
                        <!-- BAGIAN 1: INFORMASI DASAR -->
                        <div class="border-b border-gray-200 pb-6 mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <DocumentTextIcon class="w-5 h-5 mr-2 text-indigo-500"/> Informasi Dasar
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-5">
                                    <div>
                                        <InputLabel value="Judul Pengajuan" />
                                        <TextInput v-model="form.judul_pengajuan" class="w-full mt-1" />
                                        <InputError :message="frontendErrors.judul_pengajuan" />
                                    </div>
                                    
                                    <!-- Tipe Dikunci (Menggunakan Badge) -->
                                    <div>
                                        <InputLabel value="Kategori Pengajuan" class="mb-2"/>
                                        <div class="flex items-center p-3 bg-gray-50 rounded border border-gray-200">
                                            <span class="px-3 py-1 text-sm font-bold rounded uppercase tracking-wide border shadow-sm"
                                                :class="form.tipe_pengajuan === 'Langsung' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-purple-50 text-purple-700 border-purple-200'">
                                                {{ form.tipe_pengajuan === 'Langsung' ? 'Pembayaran Langsung' : 'Uang Muka (CA)' }}
                                            </span>
                                            <span class="ml-auto text-xs text-gray-400 italic">(Tidak dapat diubah)</span>
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel value="Metode Bayar" />
                                        <select v-model="form.metode_pembayaran" class="w-full border-gray-300 rounded mt-1">
                                            <option value="Transfer">Transfer Bank</option>
                                            <option value="Cash">Tunai / Kas Kecil</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="space-y-5">
                                    <div>
                                        <InputLabel value="Tanggal" />
                                        <TextInput type="date" v-model="form.tgl_pengajuan" class="w-full mt-1" />
                                        <InputError :message="frontendErrors.tgl_pengajuan" />
                                    </div>

                                    <div v-if="form.tipe_pengajuan === 'Langsung'">
                                        <InputLabel value="Lampiran Bukti" />
                                        <div v-if="props.pengajuan.attachment_path" class="mb-3 flex items-center justify-between p-3 bg-indigo-50 border border-indigo-100 rounded-lg group hover:border-indigo-300 transition cursor-pointer" @click="showFilePreview=true">
                                            <div class="flex items-center">
                                                <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg mr-3 group-hover:bg-indigo-200">
                                                    <PaperClipIcon class="w-5 h-5"/>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-indigo-900 uppercase tracking-wide">File Terlampir</p>
                                                    <p class="text-xs text-indigo-700">Klik untuk melihat preview</p>
                                                </div>
                                            </div>
                                            <EyeIcon class="w-4 h-4 text-indigo-400 group-hover:text-indigo-600"/>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <div class="flex gap-2 items-center">
                                                <input type="file" @change="handleFileChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md p-1" />
                                                <SecondaryButton v-if="previewUrl" @click="showFilePreview=true"><EyeIcon class="w-5 h-5"/></SecondaryButton>
                                            </div>
                                            <p class="text-xs text-gray-500 flex items-center">
                                                <DocumentDuplicateIcon class="w-3 h-3 mr-1" />
                                                <em>Tip: Anda bisa langsung <kbd class="bg-gray-100 border border-gray-300 rounded px-1">Ctrl+V</kbd> / Paste gambar dari clipboard</em>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN 2: TUJUAN TRANSFER -->
                        <div v-if="form.metode_pembayaran === 'Transfer'" class="border-b border-gray-200 pb-6 mb-6 bg-gray-50 p-4 rounded">
                            <h3 class="text-lg font-medium mb-4 flex items-center"><CreditCardIcon class="w-5 h-5 mr-2 text-indigo-500"/> Tujuan Transfer</h3>

                            <!-- Pilihan Sub Tipe (Hanya jika Langsung) -->
                            <div v-if="form.tipe_pengajuan === 'Langsung'" class="mb-4">
                                <InputLabel value="Jenis Penerima" class="mb-2" />
                                <div class="flex space-x-4">
                                    <label class="flex items-center cursor-pointer bg-white px-3 py-2 rounded border">
                                        <input type="radio" v-model="form.sub_tipe_penerima" value="Vendor" class="mr-2 text-indigo-600"> <BuildingStorefrontIcon class="w-4 h-4 mr-2 text-gray-500"/> Vendor
                                    </label>
                                    <label class="flex items-center cursor-pointer bg-white px-3 py-2 rounded border">
                                        <input type="radio" v-model="form.sub_tipe_penerima" value="Karyawan" class="mr-2 text-indigo-600"> <UserGroupIcon class="w-4 h-4 mr-2 text-gray-500"/> Reimburse
                                    </label>
                                </div>
                            </div>

                            <!-- Vendor Logic -->
                            <div v-if="form.tipe_pengajuan === 'Langsung' && form.sub_tipe_penerima === 'Vendor'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel value="Pilih Vendor" />
                                    <div class="flex gap-2 mt-1 relative cursor-pointer" @click="() => { searchVendorQuery = ''; showSelectVendorModal = true; }">
                                        <TextInput :value="selectedVendor ? selectedVendor.nama_vendor : ''" placeholder="Pilih Vendor..." class="w-full pointer-events-none" readonly />
                                        <SecondaryButton @click.stop="showVendorModal = true"><PlusIcon class="w-5 h-5"/></SecondaryButton>
                                    </div>
                                </div>
                                <div class="bg-white p-3 rounded border text-sm">
                                    <div class="text-xs text-gray-500 uppercase font-bold mb-1">Snapshot Data Bank</div>
                                    <div v-if="form.bank_tujuan"><b>{{ form.bank_tujuan }}</b> - {{ form.no_rek_tujuan }}<br>a.n {{ form.atas_nama_tujuan }}</div>
                                    <div v-else class="text-red-500">Data bank kosong.</div>
                                </div>
                            </div>

                            <!-- Karyawan Logic -->
                            <div v-if="form.tipe_pengajuan === 'UangMuka' || (form.tipe_pengajuan === 'Langsung' && form.sub_tipe_penerima === 'Karyawan')" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel value="Karyawan Penerima" />
                                    <select v-model="form.id_karyawan_penerima" class="w-full border-gray-300 rounded mt-1">
                                        <option v-for="k in props.masterKaryawan" :key="k.id" :value="k.id">{{ k.nama_lengkap }}</option>
                                    </select>
                                </div>
                                <div class="bg-white p-3 rounded border text-sm">
                                    <div class="text-xs text-gray-500 uppercase font-bold mb-1">Snapshot Data Bank</div>
                                    <div v-if="form.bank_tujuan"><b>{{ form.bank_tujuan }}</b> - {{ form.no_rek_tujuan }}<br>a.n {{ form.atas_nama_tujuan }}</div>
                                    <div v-else class="text-red-500">Data bank kosong.</div>
                                    <button type="button" @click="showEmployeeBankModal=true" class="text-indigo-600 underline text-xs mt-1" v-if="form.id_karyawan_penerima">Ubah Rekening?</button>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN 3: ITEMS -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-bold text-gray-700 flex items-center"><BanknotesIcon class="w-5 h-5 mr-2 text-indigo-500"/> Rincian Item</h3>
                                <SecondaryButton @click="openModalTambah"><PlusIcon class="w-4 h-4 mr-1"/> Tambah Item</SecondaryButton>
                            </div>
                            <div class="overflow-x-auto border rounded-lg shadow-sm">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="p-3 w-10">#</th><th class="p-3">Deskripsi</th><th class="p-3">Beban Anggaran</th><th class="p-3 text-right">Nominal</th><th class="p-3 text-center">Aksi</th></tr></thead>
                                    <tbody class="divide-y"><tr v-if="form.items.length===0"><td colspan="5" class="p-4 text-center text-gray-500">Kosong.</td></tr>
                                    <tr v-for="(item, idx) in form.items" :key="idx"><td class="p-3 text-center">{{ idx+1 }}</td><td class="p-3">{{ item.deskripsi_item }}</td><td class="p-3 text-sm"><b>{{ filteredPrograms.find(p=>p.id==item.id_program)?.nama_program || '...' }}</b><br>{{ filteredAkun.find(a=>a.id==item.id_akun)?.nama_akun || props.masterAkun.find(a=>a.id==item.id_akun)?.nama_akun }}</td><td class="p-3 text-right font-bold">{{ formatCurrency(item.nominal_item) }}</td><td class="p-3 text-center"><button type="button" @click="editItem(idx)" class="text-yellow-600 mx-1"><PencilSquareIcon class="w-4 h-4"/></button><button type="button" @click="removeItem(idx)" class="text-red-600 mx-1"><TrashIcon class="w-4 h-4"/></button></td></tr></tbody>
                                    <tfoot class="bg-gray-100 font-bold"><tr><td colspan="3" class="p-3 text-right">TOTAL</td><td class="p-3 text-right text-indigo-700">{{ formatCurrency(form.total_nominal_diajukan) }}</td><td></td></tr></tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- FOOTER & CATATAN -->
                        <div class="border-t border-gray-200 mt-8 pt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel value="Catatan Tambahan" />
                                    <textarea v-model="form.catatan_header" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Catatan umum untuk approver..."></textarea>
                                </div>
                                <div class="flex items-end justify-end">
                                    <PrimaryButton class="px-6 py-3 text-base" :disabled="form.processing">Simpan Perubahan</PrimaryButton>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- MODALS -->
        <Modal :show="showModal" @close="showModal=false">
            <div class="p-6 space-y-4"><h3 class="font-bold text-lg">{{ isEditingItem ? 'Edit' : 'Tambah' }} Item</h3><div><InputLabel value="Deskripsi"/><TextInput v-model="modalForm.deskripsi_item" class="w-full"/><InputError :message="modalErrors.deskripsi_item"/></div><div><InputLabel value="Program"/><select v-model="modalForm.id_program" @change="fetchAccounts" class="w-full border rounded"><option :value="null">Pilih...</option><option v-for="p in filteredPrograms" :key="p.id" :value="p.id">{{ p.nama_program }}</option></select></div><div><InputLabel value="Akun"/><select v-model="modalForm.id_akun" @change="onBudgetLineChange" class="w-full border rounded" :disabled="!modalForm.id_program"><option :value="null">Pilih...</option><option v-for="a in filteredAkun" :key="a.id" :value="a.id">{{ a.kode_akun }} - {{ a.nama_akun }}</option></select></div><div><InputLabel value="Nominal"/><InputCurrency v-model="modalForm.nominal_item" class="mt-1" :disabled="!modalForm.id_akun" /><div v-if="modalForm.id_akun" class="text-xs mt-1" :class="sisaSaldoModal >= 0 ? 'text-green-600':'text-red-600'">Sisa: {{ formatCurrency(sisaSaldoModal) }}</div><InputError :message="modalErrors.nominal_item"/></div><div class="flex justify-end"><PrimaryButton @click="simpanItem">Simpan</PrimaryButton></div></div>
        </Modal>
        <VendorModal :show="showVendorModal" @close="showVendorModal=false" @vendor-created="handleVendorCreated" />
        <EmployeeBankModal :show="showEmployeeBankModal" :employeeId="form.id_karyawan_penerima" :employeeName="selectedEmployee?.nama_lengkap" @close="showEmployeeBankModal=false" @bank-created="handleEmployeeBankCreated" />
        <Modal :show="showSelectVendorModal" @close="showSelectVendorModal=false"><div class="p-6"><TextInput v-model="searchVendorQuery" placeholder="Cari Vendor..." class="w-full mb-4" /><div class="max-h-60 overflow-y-auto divide-y"><div v-for="v in filteredVendors" :key="v.id" @click="form.id_vendor_penerima=v.id; showSelectVendorModal=false" class="p-2 hover:bg-gray-100 cursor-pointer">{{ v.nama_vendor }}</div></div></div></Modal>
        <FilePreviewModal :show="showFilePreview" @close="showFilePreview = false" :fileUrl="activeAttachmentUrl" :fileType="activeAttachmentType" :fileName="activeAttachmentName" />
    </AuthenticatedLayout>
</template>