<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatCurrency } from '@/utils/helpers';
import { ref } from 'vue';
import { 
    BanknotesIcon, 
    CalculatorIcon, 
    DocumentTextIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    FunnelIcon
} from '@heroicons/vue/24/outline';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    ppn: Object, // { payable, receivable, net_payable, payable_account_id }
    pph: Object,  // { details: [], total_payable }
    filters: Object // { start_date, end_date }
});

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilter = () => {
    router.get(route('admin.tax-report.index'), {
        start_date: startDate.value,
        end_date: endDate.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Tax Center" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tax Center (Pusat Pajak)</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Filters & Actions -->
                <div class="bg-white p-4 rounded-lg shadow-sm flex flex-col md:flex-row justify-between items-end md:items-center gap-4">
                    <div class="flex items-end gap-3 w-full md:w-auto">
                        <div>
                            <InputLabel value="Dari Tanggal" class="mb-1" />
                            <TextInput type="date" v-model="startDate" class="text-sm w-full md:w-40" />
                        </div>
                        <div>
                            <InputLabel value="Sampai Tanggal" class="mb-1" />
                            <TextInput type="date" v-model="endDate" class="text-sm w-full md:w-40" />
                        </div>
                        <PrimaryButton @click="applyFilter" class="mb-[2px]">
                            <FunnelIcon class="w-4 h-4 mr-1" /> Filter
                        </PrimaryButton>
                    </div>

                    <Link 
                        :href="route('admin.pengajuan.create', { type: 'tax_payment' })"
                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1"
                    >
                        <BanknotesIcon class="w-4 h-4" />
                        Buat Pembayaran Manual
                    </Link>
                </div>

                <!-- PPN (VAT) Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Output VAT (Hutang PPN) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 h-full">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <CalculatorIcon class="w-6 h-6 text-red-600" />
                                Hutang PPN (Keluaran)
                            </h3>

                            <div v-if="ppn.payable_details.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Akun</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in ppn.payable_details" :key="item.id">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                <Link :href="route('admin.tax-report.show', item.id)" class="hover:text-indigo-600">
                                                    {{ item.nama_akun }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-red-600 font-bold">
                                                {{ formatCurrency(item.balance) }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <Link :href="route('admin.pengajuan.create', { 
                                                          type: 'tax_payment', 
                                                          account_id: item.id, 
                                                          amount: item.balance,
                                                          desc: 'Pelunasan ' + item.nama_akun
                                                      })"
                                                      class="text-xs bg-red-50 text-red-600 px-2 py-1 rounded hover:bg-red-100">
                                                    Bayar
                                                </Link>
                                            </td>
                                        </tr>
                                        <tr class="bg-gray-50 font-bold">
                                            <td class="px-4 py-3 text-right text-sm">Total</td>
                                            <td class="px-4 py-3 text-right text-sm text-red-800">{{ formatCurrency(ppn.total_payable) }}</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 text-sm">
                                Tidak ada hutang PPN saat ini.
                            </div>
                        </div>
                    </div>

                    <!-- Input VAT (Piutang PPN) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 h-full">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <CalculatorIcon class="w-6 h-6 text-green-600" />
                                Piutang PPN (Masukan)
                            </h3>

                            <div v-if="ppn.receivable_details.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Akun</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in ppn.receivable_details" :key="item.id">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                <Link :href="route('admin.tax-report.show', item.id)" class="hover:text-indigo-600">
                                                    {{ item.nama_akun }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-green-600 font-bold">
                                                {{ formatCurrency(item.balance) }}
                                            </td>
                                        </tr>
                                        <tr class="bg-gray-50 font-bold">
                                            <td class="px-4 py-3 text-right text-sm">Total</td>
                                            <td class="px-4 py-3 text-right text-sm text-green-800">{{ formatCurrency(ppn.total_receivable) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 text-sm">
                                Tidak ada piutang PPN saat ini.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net PPN Summary -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Ringkasan PPN (Net)</h3>
                            <p class="text-sm text-gray-500">Selisih Hutang PPN - Piutang PPN</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold" :class="ppn.net_payable > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(ppn.net_payable) }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ ppn.net_payable > 0 ? 'Harus disetor ke Kas Negara' : 'Lebih Bayar (Restitusi/Kompensasi)' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PPh (Withholding Tax) Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Payable PPh (Kewajiban) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 h-full">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <DocumentTextIcon class="w-6 h-6 text-red-600" />
                                Kewajiban PPh (Hutang)
                            </h3>

                            <div v-if="pph.payable_details.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Akun</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in pph.payable_details" :key="item.id">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                <Link :href="route('admin.tax-report.show', item.id)" class="hover:text-indigo-600">
                                                    {{ item.nama_akun }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-red-600 font-bold">
                                                {{ formatCurrency(item.balance) }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <Link :href="route('admin.pengajuan.create', { 
                                                          type: 'tax_payment', 
                                                          account_id: item.id, 
                                                          amount: item.balance,
                                                          desc: 'Pelunasan ' + item.nama_akun
                                                      })"
                                                      class="text-xs bg-red-50 text-red-600 px-2 py-1 rounded hover:bg-red-100">
                                                    Bayar
                                                </Link>
                                            </td>
                                        </tr>
                                        <tr class="bg-gray-50 font-bold">
                                            <td class="px-4 py-3 text-right text-sm">Total</td>
                                            <td class="px-4 py-3 text-right text-sm text-red-800">{{ formatCurrency(pph.total_payable) }}</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 text-sm">
                                Tidak ada kewajiban PPh saat ini.
                            </div>
                        </div>
                    </div>

                    <!-- Prepaid PPh (Kredit Pajak) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 h-full">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <DocumentTextIcon class="w-6 h-6 text-green-600" />
                                Kredit Pajak PPh (Piutang)
                            </h3>

                            <div v-if="pph.prepaid_details.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Akun</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in pph.prepaid_details" :key="item.id">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                <Link :href="route('admin.tax-report.show', item.id)" class="hover:text-indigo-600">
                                                    {{ item.nama_akun }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-green-600 font-bold">
                                                {{ formatCurrency(item.balance) }}
                                            </td>
                                        </tr>
                                        <tr class="bg-gray-50 font-bold">
                                            <td class="px-4 py-3 text-right text-sm">Total</td>
                                            <td class="px-4 py-3 text-right text-sm text-green-800">{{ formatCurrency(pph.total_prepaid) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 text-sm">
                                Tidak ada kredit pajak PPh saat ini.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
