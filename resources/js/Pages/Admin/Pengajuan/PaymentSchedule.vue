<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputCurrency from '@/Components/InputCurrency.vue'; 
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    ArrowLeftIcon, PrinterIcon, BanknotesIcon, CheckCircleIcon, 
    ExclamationCircleIcon, MagnifyingGlassIcon, FunnelIcon
} from '@heroicons/vue/24/solid';
import { formatCurrency, formatDate } from '@/utils/helpers';

const props = defineProps({
    items: Array,
    filters: Object
});

// State
const selectedIds = ref([]);
const search = ref(props.filters?.search || '');
const selectedStatuses = ref(props.filters?.statuses || ['Approved']);
const paymentPlan = ref({}); // Map: id -> { rencana_bayar, keterangan }

// Available Statuses for Forecasting
const availableStatuses = ['Draft', 'Pending Approval', 'Verification', 'Revision', 'Approved'];

// Watch for filter changes and reload data via Inertia
watch([search, selectedStatuses], () => {
    router.get(route('admin.pengajuan.payment-schedule'), { 
        search: search.value, 
        statuses: selectedStatuses.value 
    }, { 
        preserveState: true, 
        replace: true 
    });
}, { deep: true });

// Initiative items map for reactivity
props.items.forEach(item => {
    paymentPlan.value[item.id] = {
        rencana_bayar: item.sisa_tagihan, // Default Full Payment
        keterangan: ''
    };
});

// Filtered Items (Search)
const filteredItems = computed(() => {
    if (!search.value) return props.items;
    return props.items.filter(item => 
        item.nomor_pengajuan.toLowerCase().includes(search.value.toLowerCase()) ||
        item.pengaju.nama_lengkap.toLowerCase().includes(search.value.toLowerCase()) ||
        (item.vendor_penerima && item.vendor_penerima.nama_vendor.toLowerCase().includes(search.value.toLowerCase()))
    );
});

// Computed Total
const totalSelected = computed(() => {
    return selectedIds.value.reduce((sum, id) => {
        const plan = paymentPlan.value[id];
        return sum + (parseFloat(plan?.rencana_bayar) || 0);
    }, 0);
});

// Form for Print Action
const form = useForm({
    items: []
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = filteredItems.value.map(i => i.id);
    } else {
        selectedIds.value = [];
    }
};

const submitPrint = () => {
    try {
        if (selectedIds.value.length === 0) return alert('Pilih minimal satu pengajuan.');
        
        // Construct payload
        const payload = selectedIds.value.map(id => ({
            id: id,
            rencana_bayar: paymentPlan.value[id].rencana_bayar,
            keterangan: paymentPlan.value[id].keterangan
        }));

        // Use a regular form submit to open in new tab (Inertia doesn't handle PDF stream in new tab natively easily for POST)
        const hiddenForm = document.createElement('form');
        hiddenForm.method = 'POST';
        hiddenForm.action = route('admin.pengajuan.print-schedule');
        hiddenForm.target = '_blank';
        
        // CSRF Token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (!csrfMeta) throw new Error('CSRF Token not found');
        
        const csrfToken = csrfMeta.getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        hiddenForm.appendChild(csrfInput);

        payload.forEach((item, index) => {
            for (const key in item) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${key}]`;
                input.value = item[key] || ''; // Handle undefined/null
                hiddenForm.appendChild(input);
            }
        });

        document.body.appendChild(hiddenForm);
        hiddenForm.submit();
        
        // Remove slightly later to ensure submit happens
        setTimeout(() => {
            document.body.removeChild(hiddenForm);
        }, 100);

    } catch (error) {
        console.error("Print Schedule Error:", error);
        alert("Gagal mencetak jadwal: " + error.message);
    }
};
</script>

<template>
    <Head title="Rencana Pembayaran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.pengajuan.index')" class="p-2 rounded-full hover:bg-gray-100 transition">
                        <ArrowLeftIcon class="w-5 h-5 text-gray-600"/>
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rencana Pembayaran</h2>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">Payment Schedule / Disbursement Plan</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8 pb-32">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- FILTER & TOOLBAR -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="relative w-full md:w-1/3">
                        <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"/>
                        <TextInput v-model="search" placeholder="Cari No Pengajuan, Nama, atau Vendor..." class="pl-10 w-full" />
                    </div>
                    
                    <!-- Status Filter Checkboxes -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-2 flex items-center">
                            <FunnelIcon class="w-4 h-4 mr-1"/> Filter Status (Proyeksi)
                        </div>
                        <label v-for="status in availableStatuses" :key="status" class="inline-flex items-center bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-full cursor-pointer hover:bg-gray-100 transition">
                            <input type="checkbox" :value="status" v-model="selectedStatuses" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-3.5 h-3.5 mr-2">
                            <span class="text-xs font-medium text-gray-700">{{ status }}</span>
                        </label>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto min-h-[400px]">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 w-10 text-center">
                                        <input type="checkbox" @change="toggleSelectAll" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Pengajuan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Penerima (Vendor/User)</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Sisa Tagihan</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase w-48">Rencana Bayar</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase w-64">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="item in filteredItems" :key="item.id" 
                                    :class="selectedIds.includes(item.id) ? 'bg-indigo-50/50' : 'hover:bg-gray-50'">
                                    
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox" :value="item.id" v-model="selectedIds" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    </td>
                                    
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ item.nomor_pengajuan }}</div>
                                        <div class="text-xs text-gray-500">{{ formatDate(item.tgl_pengajuan) }}</div>
                                        <div class="text-[10px] font-bold px-2 py-0.5 rounded-full inline-block mt-1 border" 
                                             :class="item.status_global === 'Approved' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200'">
                                            {{ item.status_global }}
                                        </div>
                                        <div class="text-xs text-indigo-600 mt-1 font-medium">{{ item.judul_pengajuan }}</div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <template v-if="item.vendor_penerima">
                                            <div class="text-sm font-bold text-gray-900">{{ item.vendor_penerima.nama_vendor }}</div>
                                            <div class="text-xs text-gray-500">Vendor</div>
                                        </template>
                                        <template v-else-if="item.karyawan_penerima">
                                            <div class="text-sm font-bold text-gray-900">{{ item.karyawan_penerima.nama_lengkap }}</div>
                                            <div class="text-xs text-gray-500">Karyawan</div>
                                        </template>
                                        <template v-else>
                                            <div class="text-sm font-bold text-gray-900">{{ item.pengaju.nama_lengkap }}</div>
                                            <div class="text-xs text-gray-500">Pengaju</div>
                                        </template>
                                        
                                        <!-- Bank Info -->
                                        <div v-if="item.bank_tujuan" class="mt-2 text-xs bg-gray-50 p-1 rounded border border-gray-200">
                                            {{ item.bank_tujuan }} - {{ item.no_rek_tujuan }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-bold text-gray-700">{{ formatCurrency(item.sisa_tagihan) }}</div>
                                        <div class="text-xs text-gray-400">Total: {{ formatCurrency(item.total_nominal_diajukan) }}</div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <InputCurrency v-model="paymentPlan[item.id].rencana_bayar" 
                                            :class="{'border-indigo-500 ring-1 ring-indigo-500': selectedIds.includes(item.id)}"
                                            class="text-right font-bold w-full" 
                                        />
                                        <div v-if="paymentPlan[item.id].rencana_bayar < item.sisa_tagihan" class="text-[10px] text-orange-600 font-bold mt-1 text-right">
                                            Partial Payment
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <TextInput v-model="paymentPlan[item.id].keterangan" placeholder="Catatan..." class="text-sm w-full"/>
                                    </td>
                                </tr>
                                <tr v-if="filteredItems.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                        <BanknotesIcon class="w-12 h-12 mx-auto mb-2 text-gray-300"/>
                                        Tidak ada pengajuan siap bayar ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- FLOATING FOOTER -->
        <div class="fixed bottom-0 left-0 w-full bg-white border-t border-indigo-200 p-4 shadow-lg z-50 flex justify-between items-center md:px-12 bg-indigo-50">
            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-600">Terpilih: <b class="text-indigo-700">{{ selectedIds.length }}</b> Item</div>
                <div class="h-8 w-px bg-indigo-200 hidden md:block"></div>
                <div class="flex flex-col">
                    <span class="text-xs text-indigo-600 font-bold uppercase tracking-wider">Total Rencana Bayar</span>
                    <span class="text-xl font-bold text-gray-900">{{ formatCurrency(totalSelected) }}</span>
                </div>
            </div>
            <PrimaryButton @click="submitPrint" class="bg-indigo-600 hover:bg-indigo-700 border-indigo-600 shadow-lg px-6 py-3" :disabled="selectedIds.length === 0">
                <PrinterIcon class="w-5 h-5 mr-2"/>
                Cetak Jadwal Pembayaran (PDF)
            </PrimaryButton>
        </div>

    </AuthenticatedLayout>
</template>
