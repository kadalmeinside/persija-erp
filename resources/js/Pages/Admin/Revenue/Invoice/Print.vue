<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    invoice: Object,
    timestamp: String,
    company: Object
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (str) => str ? new Date(str).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 1000);
});
</script>

<template>
    <Head :title="`Invoice ${invoice.nomor_invoice}`" />

    <div class="min-h-screen bg-gray-200 p-8 print:p-0 print:bg-white font-sans text-gray-900 print:text-black">
        <div class="page-container bg-white shadow-xl print:shadow-none mx-auto p-[15mm]">
            
            <!-- Header -->
            <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gray-100 flex items-center justify-center border border-gray-300 rounded overflow-hidden">
                        <img v-if="company.logo" :src="company.logo" alt="Logo" class="w-full h-full object-contain">
                        <span v-else class="text-[8px] font-bold text-gray-400">LOGO</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold uppercase tracking-wider text-gray-800 leading-tight">INVOICE</h1>
                        <p class="text-xs font-semibold text-gray-600 mt-1">{{ company.name }}</p>
                        <p class="text-[10px] text-gray-500 whitespace-pre-line">{{ company.address }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-lg font-mono font-bold text-gray-800">{{ invoice.nomor_invoice }}</h2>
                    <div class="text-xs mt-1">
                        <span class="font-bold">Tanggal:</span> {{ formatDate(invoice.tgl_invoice) }}
                    </div>
                    <div class="text-xs">
                        <span class="font-bold">Jatuh Tempo:</span> {{ formatDate(invoice.tgl_jatuh_tempo) }}
                    </div>
                    <div class="mt-2 border rounded px-2 py-1 text-[10px] font-bold uppercase inline-block border-black text-black">
                        {{ invoice.status }}
                    </div>
                </div>
            </div>

            <!-- Client Info -->
            <div class="mb-8">
                <h3 class="font-bold border-b border-gray-200 mb-2 pb-1 uppercase text-[10px] text-gray-500 tracking-wider">Ditagihkan Kepada</h3>
                <div class="text-sm">
                    <p class="font-bold text-gray-900">{{ invoice.pelanggan?.nama_pelanggan }}</p>
                    <p class="text-gray-600">{{ invoice.pelanggan?.alamat }}</p>
                    <p class="text-gray-600">{{ invoice.pelanggan?.telepon }}</p>
                    <p class="text-gray-600">{{ invoice.pelanggan?.email }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6">
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100 print:bg-gray-200">
                            <th class="py-2 px-2 border border-gray-300 text-left">Deskripsi</th>
                            <th class="py-2 px-2 border border-gray-300 text-right w-24">Kuantitas</th>
                            <th class="py-2 px-2 border border-gray-300 text-right w-32">Harga Satuan</th>
                            <th class="py-2 px-2 border border-gray-300 text-right w-32">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice.detail" :key="item.id">
                            <td class="py-1.5 px-2 border border-gray-300">
                                <div class="font-medium">{{ item.deskripsi_item }}</div>
                                <div class="text-[10px] text-gray-500">{{ item.akun_pendapatan?.nama_akun }}</div>
                            </td>
                            <td class="py-1.5 px-2 border border-gray-300 text-right">{{ item.kuantitas }}</td>
                            <td class="py-1.5 px-2 border border-gray-300 text-right">{{ formatCurrency(item.harga_satuan) }}</td>
                            <td class="py-1.5 px-2 border border-gray-300 text-right font-mono">{{ formatCurrency(item.total_harga) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="flex justify-end mb-8">
                <div class="w-1/2">
                    <table class="w-full text-xs">
                        <tr>
                            <td class="py-1 text-right pr-4 text-gray-600">Subtotal</td>
                            <td class="py-1 text-right font-mono w-32">{{ formatCurrency(invoice.subtotal) }}</td>
                        </tr>
                        <tr v-if="invoice.ppn_amount > 0">
                            <td class="py-1 text-right pr-4 text-gray-600">PPN ({{ invoice.ppn_rate }}%)</td>
                            <td class="py-1 text-right font-mono">{{ formatCurrency(invoice.ppn_amount) }}</td>
                        </tr>
                        <tr class="border-t border-gray-300 font-bold text-sm">
                            <td class="py-2 text-right pr-4">Total Tagihan</td>
                            <td class="py-2 text-right font-mono">{{ formatCurrency(invoice.total_tagihan) }}</td>
                        </tr>
                        <tr v-if="invoice.pph_amount > 0">
                            <td class="py-1 text-right pr-4 text-red-600">Potongan PPh ({{ invoice.pph_rate }}%)</td>
                            <td class="py-1 text-right font-mono text-red-600">- {{ formatCurrency(invoice.pph_amount) }}</td>
                        </tr>
                        <tr class="font-bold text-sm text-green-700">
                            <td class="py-2 text-right pr-4">Net Diterima</td>
                            <td class="py-2 text-right font-mono">{{ formatCurrency(invoice.total_tagihan - invoice.pph_amount) }}</td>
                        </tr>
                        <tr class="border-t-2 border-gray-800 font-bold text-base mt-2">
                            <td class="py-4 text-right pr-4">Sisa Tagihan</td>
                            <td class="py-4 text-right font-mono">{{ formatCurrency(invoice.sisa_tagihan) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Approvals / Signatures -->
            <div class="mb-8" v-if="invoice.approval_process && invoice.approval_process.length > 0">
                <div class="flex justify-end gap-12 text-center text-xs">
                    <template v-for="approval in invoice.approval_process" :key="approval.id">
                        <div v-if="approval.status !== 'Skipped'" class="w-32 flex flex-col items-center">
                            <p class="font-bold text-gray-500 mb-2 uppercase text-[10px] tracking-wider">
                                {{ approval.label_aksi || 'Persetujuan' }}
                            </p>
                            
                            <div class="h-[60px] flex items-center justify-center mb-1">
                                <img v-if="approval.status === 'Approved' && approval.qr_code" :src="approval.qr_code" alt="QR Code" class="h-[60px] w-[60px]" />
                            </div>
                            
                            <p class="font-bold underline">
                                <template v-if="approval.status === 'Approved'">
                                    {{ approval.action_karyawan?.nama_lengkap || '(System)' }}
                                </template>
                                <template v-else>
                                    ( {{ approval.target_karyawan?.nama_lengkap || '................' }} )
                                </template>
                            </p>
                            <p v-if="approval.status === 'Approved' && approval.tgl_aksi" class="text-[10px] text-gray-500 mt-1">
                                Tgl: {{ new Date(approval.tgl_aksi).toLocaleDateString('id-ID', { year: 'numeric', month: '2-digit', day: '2-digit' }) }}
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-10 text-xs border border-dashed border-gray-300 p-2 rounded" v-if="invoice.catatan">
                <span class="font-bold text-gray-500 mr-2">Catatan:</span>
                <span class="italic">"{{ invoice.catatan }}"</span>
            </div>

            <!-- Footer -->
            <div class="absolute bottom-4 left-0 w-full text-center text-[8px] text-gray-400 px-[15mm]">
                <div class="border-t border-gray-300 pt-1">
                    Dicetak: {{ timestamp }} | Sistem ERP Persija
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
@media print {
    @page { size: A4; margin: 0; }
    body { background-color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .page-container { width: 210mm; min-height: 297mm; height: 297mm; padding: 15mm; margin: 0; box-shadow: none; page-break-after: always; position: relative; }
    .page-container:last-child { page-break-after: auto; }
    .no-print { display: none; }
}
@media screen {
    .page-container { width: 210mm; min-height: 297mm; margin-bottom: 2rem; }
}
</style>
