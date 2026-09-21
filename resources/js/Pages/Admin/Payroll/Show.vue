<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputCurrency from '@/Components/InputCurrency.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ArrowLeftIcon, CheckCircleIcon, PencilIcon, ClockIcon, DocumentTextIcon, PrinterIcon } from '@heroicons/vue/24/outline';
import ActivityLogList from '@/Components/ActivityLogList.vue';

const props = defineProps({
    payroll: Object,
    kasBanks: Array,
    allocationGroups: Array,
    employees: Array,
    komponenGaji: Array,
    programs: Array
});

const page = usePage();

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const activeTab = ref('detail');


const showApproveModal = ref(false);
const approveForm = useForm({
    allocations: {}
});

// Initialize allocations when modal opens or props change
watch(() => props.allocationGroups, (groups) => {
    if (groups) {
        groups.forEach(group => {
            // Auto-select if only 1 program exists
            if (group.programs.length === 1) {
                approveForm.allocations[group.id_departemen] = group.programs[0].id;
            } else {
                approveForm.allocations[group.id_departemen] = '';
            }
        });
    }
}, { immediate: true });

const approvePayroll = () => {
    approveForm.post(route('admin.payrolls.approve', props.payroll.id), {
        onSuccess: () => showApproveModal.value = false
    });
};

// Edit Modal Logic
const showEditModal = ref(false);
const selectedDetail = ref(null);

const programOptions = computed(() => {
    return props.programs.map(prog => ({
        value: prog.id,
        label: `${prog.nama_program} (${prog.departemen?.nama_departemen || '-'})`
    }));
});

const editForm = useForm({
    gaji_pokok: 0, // Allow editing basic salary too for flexibility
    total_tunjangan: 0,
    honorarium: 0,
    lembur: 0,
    total_potongan: 0
});

const openEditModal = (detail) => {
    selectedDetail.value = detail;
    editForm.gaji_pokok = detail.gaji_pokok;
    editForm.total_tunjangan = detail.total_tunjangan;
    editForm.honorarium = detail.honorarium;
    editForm.lembur = detail.lembur;
    editForm.total_potongan = detail.total_potongan;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedDetail.value = null;
    editForm.reset();
};

const updateDetail = () => {
    if (!selectedDetail.value) return;
    
    editForm.put(route('admin.payrolls.details.update', [props.payroll.id, selectedDetail.value.id]), {
        onSuccess: () => closeEditModal()
    });
};

// Add Recipient Modal Logic
const showAddModal = ref(false);
const addForm = useForm({
    id_karyawan: '',
    id_program: '',
    id_komponen_gaji: '',
    honorarium: 0,
    total_tunjangan: 0,
    lembur: 0,
    total_potongan: 0
});

const closeAddModal = () => {
    showAddModal.value = false;
    addForm.reset();
};

const storeDetail = () => {
    addForm.post(route('admin.payrolls.details.store', props.payroll.id), {
        onSuccess: () => closeAddModal()
    });
};
</script>

<template>
    <Head :title="`Payroll ${payroll.bulan_periode}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Payroll</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-between items-center">
                    <Link :href="route('admin.payrolls.index')" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        <ArrowLeftIcon class="w-4 h-4 mr-2" /> Kembali
                    </Link>
                    
                    <div v-if="payroll.status === 'Draft'">
                        <PrimaryButton @click="showApproveModal = true" class="bg-green-600 hover:bg-green-700">
                            <CheckCircleIcon class="w-4 h-4 mr-2" /> Setujui & Bayar
                        </PrimaryButton>
                    </div>
                </div>

                <!-- TABS NAVIGATION -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <a href="#" @click.prevent="activeTab = 'detail'" 
                           :class="[activeTab === 'detail' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center transition']">
                           <DocumentTextIcon class="w-4 h-4 mr-2"/> Detail Payroll
                        </a>
                        <a href="#" @click.prevent="activeTab = 'activity'" 
                           :class="[activeTab === 'activity' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center transition']">
                           <ClockIcon class="w-4 h-4 mr-2"/> Riwayat Log
                        </a>
                    </nav>
                </div>

                <!-- TAB CONTENT: DETAIL -->
                <div v-show="activeTab === 'detail'">
                    <!-- Header Info -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Periode</h4>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ payroll.bulan_periode }}</p>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Total Gaji Kotor</h4>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(payroll.total_gaji_kotor) }}</p>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Total Potongan</h4>
                                <p class="text-lg font-bold text-red-600">{{ formatCurrency(payroll.total_potongan) }}</p>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Total Dibayarkan</h4>
                                <p class="text-lg font-bold text-green-600">{{ formatCurrency(payroll.total_gaji_bersih) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Employee List -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-gray-900 dark:text-white">Rincian Per Karyawan</h3>
                            <PrimaryButton v-if="payroll.status === 'Draft'" @click="showAddModal = true" class="bg-blue-600 hover:bg-blue-700">
                                + Tambah Penerima
                            </PrimaryButton>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Karyawan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Departemen</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gaji Pokok</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tunjangan</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Honorarium</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Lembur</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Potongan</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Take Home Pay</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="detail in payroll.details" :key="detail.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ detail.karyawan?.nama_lengkap }}<br>
                                            <span class="text-xs text-gray-500">{{ detail.karyawan?.jabatan }}</span>
                                            
                                            <!-- Breakdown Display -->
                                            <div v-if="detail.rincian_komponen && detail.rincian_komponen.length > 0" class="mt-2 space-y-1">
                                                <div v-for="(item, idx) in detail.rincian_komponen" :key="idx" class="text-xs flex justify-between items-center bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                    <span class="text-gray-600 dark:text-gray-300">{{ item.type }}</span>
                                                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ formatCurrency(item.amount || item.nilai) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ detail.karyawan?.departemen?.nama_departemen || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(detail.gaji_pokok) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(detail.total_tunjangan) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(detail.honorarium) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(detail.lembur) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">{{ formatCurrency(detail.total_potongan) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-green-600">{{ formatCurrency(detail.gaji_bersih) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button v-if="payroll.status === 'Draft'" @click="openEditModal(detail)" class="text-indigo-600 hover:text-indigo-900 mr-2" title="Edit Rincian">
                                                <PencilIcon class="w-5 h-5 inline-block" />
                                            </button>
                                            <a :href="route('admin.payrolls.details.print', [payroll.id, detail.id])" target="_blank" class="text-green-600 hover:text-green-900" title="Cetak Slip Gaji">
                                                <PrinterIcon class="w-5 h-5 inline-block" />
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB CONTENT: ACTIVITY LOG -->
                <div v-show="activeTab === 'activity'">
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <ActivityLogList :activities="payroll.activities" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <Modal :show="showApproveModal" @close="showApproveModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Setujui & Alokasikan Payroll</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Silakan pilih <strong>Program Kerja</strong> untuk pembebanan gaji tiap departemen.
                </p>
                
                <form @submit.prevent="approvePayroll">
                    <div class="space-y-4 mb-6 max-h-96 overflow-y-auto">
                        <div v-for="(group, index) in allocationGroups" :key="group.id_departemen" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ group.nama_departemen }}</h3>
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                    Total: {{ formatCurrency(group.total_gaji_bersih) }}
                                </span>
                            </div>
                            
                            <div>
                                <InputLabel value="Pilih Program Anggaran" />
                                <select 
                                    v-model="approveForm.allocations[group.id_departemen]" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                    required
                                >
                                    <option value="" disabled>Pilih Program</option>
                                    <option v-for="prog in group.programs" :key="prog.id" :value="prog.id">
                                        {{ prog.nama_program }}
                                    </option>
                                </select>
                                <p v-if="group.programs.length === 0" class="text-xs text-red-500 mt-1">
                                    Tidak ada program kerja di departemen ini.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mt-6">
                        <SecondaryButton @click="showApproveModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="approveForm.processing">Setujui & Proses</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Detail Modal -->
        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Edit Rincian Gaji</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Ubah tunjangan dan potongan untuk karyawan: <strong>{{ selectedDetail?.karyawan?.nama_lengkap }}</strong>
                </p>
                
                <form @submit.prevent="updateDetail">
                    <div class="mb-4">
                        <InputLabel value="Gaji Pokok" />
                        <InputCurrency v-model="editForm.gaji_pokok" class="mt-1 block w-full" required />
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Total Tunjangan" />
                        <InputCurrency v-model="editForm.total_tunjangan" class="mt-1 block w-full" required />
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Honorarium" />
                        <InputCurrency v-model="editForm.honorarium" class="mt-1 block w-full" required />
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Total Lembur" />
                        <InputCurrency v-model="editForm.lembur" class="mt-1 block w-full" required />
                    </div>

                    <div class="mb-6">
                        <InputLabel value="Total Potongan" />
                        <InputCurrency v-model="editForm.total_potongan" class="mt-1 block w-full" required />
                    </div>

                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="closeEditModal">Batal</SecondaryButton>
                        <PrimaryButton :disabled="editForm.processing">Simpan Perubahan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
        <!-- Add Recipient Modal -->
        <Modal :show="showAddModal" @close="closeAddModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tambah Penerima Payroll</h2>
                
                <form @submit.prevent="storeDetail">
                    <div class="mb-4">
                        <InputLabel value="Pilih Karyawan / Freelance" />
                        <select v-model="addForm.id_karyawan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="" disabled>Pilih Nama</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.nama_lengkap }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program Kerja (Anggaran)</label>
                            <SearchableSelect
                                v-model="addForm.id_program"
                                :options="programOptions"
                                label-field="label"
                                value-field="value"
                                placeholder="Cari Program Kerja..."
                            />
                            <p class="text-xs text-gray-500 mt-1">Pilih program yang akan menanggung biaya ini.</p>
                        </div>
                        <div>
                            <InputLabel value="Jenis Pembayaran" />
                            <select v-model="addForm.id_komponen_gaji" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Default (Gaji/Honor)</option>
                                <option v-for="komp in komponenGaji" :key="komp.id" :value="komp.id">
                                    {{ komp.nama_komponen }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Opsional: Untuk menentukan akun GL spesifik.</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Honorarium" />
                        <InputCurrency v-model="addForm.honorarium" class="mt-1 block w-full" required />
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Total Tunjangan" />
                        <InputCurrency v-model="addForm.total_tunjangan" class="mt-1 block w-full" />
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Total Lembur" />
                        <InputCurrency v-model="addForm.lembur" class="mt-1 block w-full" />
                    </div>

                    <div class="mb-6">
                        <InputLabel value="Total Potongan" />
                        <InputCurrency v-model="addForm.total_potongan" class="mt-1 block w-full" />
                    </div>

                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="closeAddModal">Batal</SecondaryButton>
                        <PrimaryButton :disabled="addForm.processing">Tambahkan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
