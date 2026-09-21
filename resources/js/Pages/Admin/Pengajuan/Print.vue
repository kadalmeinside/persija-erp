<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted, computed, ref } from 'vue';

const props = defineProps({
    pengajuan: Object,
    timestamp: String,
    company: Object
});

// --- HELPERS ---
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (str) => str ? new Date(str).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '-';

// --- LOGIC LAMPIRAN & PDF RENDERER ---
const hasAttachment = computed(() => !!props.pengajuan.attachment_path);
const attachmentUrl = computed(() => props.pengajuan.attachment_path ? `/storage/${props.pengajuan.attachment_path}` : '');

const isImage = computed(() => {
    if (!hasAttachment.value) return false;
    return props.pengajuan.attachment_path.toLowerCase().match(/\.(jpg|jpeg|png|webp)$/);
});

const isPdf = computed(() => {
    if (!hasAttachment.value) return false;
    return props.pengajuan.attachment_path.toLowerCase().endsWith('.pdf');
});

// State untuk PDF Pages (Array of Images)
const pdfPages = ref([]);
const isLoadingPdf = ref(false);

// Fungsi Load PDF menggunakan PDF.js
const loadPdf = async () => {
    if (!isPdf.value) return;
    isLoadingPdf.value = true;

    try {
        // 1. Load Library dari CDN jika belum ada
        if (!window.pdfjsLib) {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        // 2. Init Worker
        const pdfjsLib = window.pdfjsLib;
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // 3. Fetch Document
        const loadingTask = pdfjsLib.getDocument(attachmentUrl.value);
        const pdf = await loadingTask.promise;

        // 4. Render Setiap Halaman ke Canvas -> Image Data URL
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            
            // Scale 2.0 untuk kualitas cetak yang tajam
            const viewport = page.getViewport({ scale: 2.0 });
            
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;

            // Simpan hasil render sebagai gambar JPEG
            pdfPages.value.push(canvas.toDataURL('image/jpeg', 0.9));
        }

    } catch (error) {
        console.error("Gagal merender PDF:", error);
    } finally {
        isLoadingPdf.value = false;
        // Trigger print setelah PDF selesai di-render
        setTimeout(() => window.print(), 1000);
    }
};

// Auto Print saat halaman dimuat
onMounted(() => {
    if (isPdf.value) {
        // Jika PDF, load dulu kontennya baru print
        loadPdf();
    } else {
        // Jika gambar/biasa, langsung print dengan delay kecil
        setTimeout(() => {
            window.print();
        }, 1500); 
    }
});
</script>

<template>
    <Head :title="`Cetak - ${pengajuan.nomor_pengajuan}`" />

    <!-- WRAPPER UTAMA -->
    <div class="min-h-screen bg-gray-200 p-8 print:p-0 print:bg-white font-sans text-gray-900 print:text-black">
        
        <!-- ================= HALAMAN 1: FORMULIR PENGAJUAN ================= -->
        <div class="page-container bg-white shadow-xl print:shadow-none mx-auto p-[15mm]">
            
            <!-- Header / Kop -->
            <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gray-100 flex items-center justify-center border border-gray-300 rounded overflow-hidden">
                        <img v-if="company.logo" :src="company.logo" alt="Logo" class="w-full h-full object-contain">
                        <span v-else class="text-[8px] font-bold text-gray-400">LOGO</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold uppercase tracking-wider text-gray-800 leading-tight">Formulir Pengajuan Dana</h1>
                        <p class="text-xs font-semibold text-gray-600 mt-1">{{ company.name }}</p>
                        <p class="text-[10px] text-gray-500 whitespace-pre-line">{{ company.address }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-lg font-mono font-bold text-gray-800">{{ pengajuan.nomor_pengajuan }}</h2>
                    <div class="text-xs mt-1">
                        <span class="font-bold">Tanggal:</span> {{ formatDate(pengajuan.tgl_pengajuan) }}
                    </div>
                    <div class="mt-2 border rounded px-2 py-1 text-[10px] font-bold uppercase inline-block border-black text-black">
                        {{ pengajuan.status_global }}
                    </div>
                </div>
            </div>

            <!-- Grid Informasi Utama -->
            <div class="grid grid-cols-2 gap-8 mb-6 text-xs">
                <div class="border rounded p-3 border-gray-300">
                    <h3 class="font-bold border-b border-gray-200 mb-2 pb-1 uppercase text-[10px] text-gray-500 tracking-wider">Informasi Pemohon</h3>
                    <table class="w-full">
                        <tr><td class="py-0.5 w-24 text-gray-500">Nama</td><td class="font-semibold">: {{ pengajuan.pengaju?.nama_lengkap }}</td></tr>
                        <tr><td class="py-0.5 text-gray-500">NIK / ID</td><td>: {{ pengajuan.pengaju?.nomor_induk_karyawan || '-' }}</td></tr>
                        <tr><td class="py-0.5 text-gray-500">Jabatan</td><td>: {{ pengajuan.pengaju?.jabatan }}</td></tr>
                        <tr><td class="py-0.5 text-gray-500">Departemen</td><td>: {{ pengajuan.departemen?.nama_departemen }}</td></tr>
                    </table>
                </div>

                <div class="border rounded p-3 border-gray-300">
                    <h3 class="font-bold border-b border-gray-200 mb-2 pb-1 uppercase text-[10px] text-gray-500 tracking-wider">Detail Pembayaran</h3>
                    <table class="w-full">
                        <tr><td class="py-0.5 w-24 text-gray-500">Jenis</td><td class="font-semibold uppercase">: {{ pengajuan.tipe_pengajuan === 'Langsung' ? 'Pembayaran Langsung' : 'Uang Muka (CA)' }}</td></tr>
                        <tr><td class="py-0.5 text-gray-500">Metode</td><td class="uppercase">: {{ pengajuan.metode_pembayaran }}</td></tr>
                        <tr><td class="py-0.5 text-gray-500 align-top">Keperluan</td><td class="align-top font-medium">: {{ pengajuan.judul_pengajuan }}</td></tr>
                    </table>
                </div>
            </div>

            <!-- Snapshot Tujuan Transfer -->
            <div v-if="pengajuan.metode_pembayaran === 'Transfer'" class="mb-6 border rounded p-3 bg-gray-50 print:bg-white print:border-gray-300 text-xs">
                <h3 class="font-bold uppercase text-[10px] text-gray-500 mb-2 tracking-wider">Tujuan Transfer (Snapshot)</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <span class="block text-[10px] text-gray-500">Penerima</span>
                        <span class="font-bold uppercase">
                            {{ pengajuan.tipe_pengajuan === 'Langsung' ? (pengajuan.vendor_penerima?.nama_vendor || 'Vendor') : (pengajuan.karyawan_penerima?.nama_lengkap || 'Karyawan') }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-gray-500">Bank</span>
                        <span class="font-semibold">{{ pengajuan.bank_tujuan || '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-gray-500">No. Rekening</span>
                        <span class="font-mono font-bold text-sm">{{ pengajuan.no_rek_tujuan || '-' }}</span>
                        <span class="block text-[10px] italic truncate" v-if="pengajuan.atas_nama_tujuan">a.n {{ pengajuan.atas_nama_tujuan }}</span>
                    </div>
                </div>
            </div>

            <!-- Tabel Rincian -->
            <div class="mb-6">
                <h3 class="font-bold border-b-2 border-gray-800 mb-2 pb-1 uppercase text-xs text-gray-700">
                    {{ pengajuan.tipe_pengajuan === 'UangMuka' ? 'Rincian Alokasi Anggaran' : 'Rincian Item Biaya' }}
                </h3>
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100 print:bg-gray-200">
                            <th class="py-2 px-2 border border-gray-300 text-center w-10">No</th>
                            <th class="py-2 px-2 border border-gray-300 text-left">Deskripsi Item</th>
                            <th class="py-2 px-2 border border-gray-300 text-left w-1/3">Beban Anggaran (COA)</th>
                            <th class="py-2 px-2 border border-gray-300 text-right w-32">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in pengajuan.detail" :key="item.id">
                            <td class="py-1.5 px-2 border border-gray-300 text-center">{{ index + 1 }}</td>
                            <td class="py-1.5 px-2 border border-gray-300 font-medium">{{ item.deskripsi_item }}</td>
                            <td class="py-1.5 px-2 border border-gray-300 text-[10px]">
                                <div class="font-semibold">{{ item.program_kerja?.nama_program }}</div>
                                <div>{{ item.akun_gl?.kode_akun }} - {{ item.akun_gl?.nama_akun }}</div>
                            </td>
                            <td class="py-1.5 px-2 border border-gray-300 text-right font-mono">
                                {{ formatCurrency(item.nominal_item) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 print:bg-gray-100 font-bold">
                            <td colspan="3" class="py-2 px-2 border border-gray-300 text-right uppercase">Total Disetujui</td>
                            <td class="py-2 px-2 border border-gray-300 text-right text-sm">{{ formatCurrency(pengajuan.total_nominal_diajukan) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Catatan -->
            <div class="mb-10 text-xs border border-dashed border-gray-300 p-2 rounded" v-if="pengajuan.catatan_header">
                <span class="font-bold text-gray-500 mr-2">Catatan:</span>
                <span class="italic">"{{ pengajuan.catatan_header }}"</span>
            </div>

            <!-- Tanda Tangan -->
            <div class="grid grid-cols-3 gap-4 text-center text-xs break-inside-avoid mt-auto">
                <div>
                    <p class="mb-16">Diajukan Oleh,</p>
                    <p class="font-bold underline">{{ pengajuan.pengaju?.nama_lengkap }}</p>
                    <p class="text-[10px] text-gray-500">Tgl: {{ formatDate(pengajuan.created_at) }}</p>
                </div>
                <div>
                    <p class="mb-16">Diketahui (Dept Head),</p>
                    <p class="font-bold underline">( ........................... )</p>
                </div>
                <div>
                    <p class="mb-16">Disetujui (Finance),</p>
                    <p class="font-bold underline">( ........................... )</p>
                </div>
            </div>

            <!-- Footer Halaman 1 -->
            <div class="absolute bottom-4 left-0 w-full text-center text-[8px] text-gray-400 px-[15mm]">
                <div class="border-t border-gray-300 pt-1">
                    Dicetak: {{ timestamp }} | Page 1 of {{ hasAttachment ? '2' : '1' }} | Sistem ERP Persija
                </div>
            </div>
        </div>

        <!-- ================= HALAMAN 2: LAMPIRAN (JIKA ADA) ================= -->
        <!-- PERBAIKAN: Gunakan loop untuk PDF Pages -->
        <template v-if="hasAttachment">
            
            <!-- KASUS 1: FILE GAMBAR BIASA -->
            <div v-if="isImage" class="page-container bg-white shadow-xl print:shadow-none mx-auto p-[15mm] mt-8 print:mt-0 break-before-page relative flex flex-col">
                 <div class="border-b-2 border-gray-800 pb-3 mb-6 flex justify-between items-end">
                    <div><h2 class="text-lg font-bold text-gray-800 uppercase">Lampiran Bukti</h2><p class="text-xs text-gray-500">Ref No: {{ pengajuan.nomor_pengajuan }}</p></div>
                    <div class="text-xs text-gray-400">Image Attachment</div>
                </div>
                <div class="flex-1 flex flex-col items-center justify-center border border-gray-200 bg-gray-50 print:bg-white print:border-0 rounded p-4 overflow-hidden">
                    <img :src="`/storage/${pengajuan.attachment_path}`" class="max-w-full max-h-[240mm] object-contain shadow-sm print:shadow-none" />
                </div>
                 <div class="absolute bottom-4 left-0 w-full text-center text-[8px] text-gray-400 px-[15mm]">
                    <div class="border-t border-gray-300 pt-1">Dicetak: {{ timestamp }} | Page 2 of 2 | Sistem ERP Persija</div>
                </div>
            </div>

            <!-- KASUS 2: FILE PDF (RENDER PER HALAMAN) -->
            <template v-if="isPdf">
                <!-- Loop setiap halaman hasil render PDF.js -->
                <div v-for="(pageImg, index) in pdfPages" :key="index" class="page-container bg-white shadow-xl print:shadow-none mx-auto p-[15mm] mt-8 print:mt-0 break-before-page relative flex flex-col">
                    <div class="border-b-2 border-gray-800 pb-3 mb-6 flex justify-between items-end">
                        <div><h2 class="text-lg font-bold text-gray-800 uppercase">Lampiran PDF</h2><p class="text-xs text-gray-500">Ref No: {{ pengajuan.nomor_pengajuan }}</p></div>
                        <div class="text-xs text-gray-400">Page {{ index + 1 }} of {{ pdfPages.length }}</div>
                    </div>
                    
                    <div class="flex-1 flex flex-col items-center justify-start border border-gray-200 bg-gray-50 print:bg-white print:border-0 rounded p-0 overflow-hidden">
                        <!-- Tampilkan gambar hasil render PDF -->
                        <img :src="pageImg" class="w-full h-auto shadow-sm print:shadow-none" />
                    </div>

                    <div class="absolute bottom-4 left-0 w-full text-center text-[8px] text-gray-400 px-[15mm]">
                        <div class="border-t border-gray-300 pt-1">Dicetak: {{ timestamp }} | Attachment Page {{ index + 1 }} | Sistem ERP Persija</div>
                    </div>
                </div>

                <!-- Loading Indicator (Tampil di Layar Saja) -->
                <div v-if="isLoadingPdf" class="text-center p-10 bg-white mt-4 rounded shadow no-print">
                    <p class="text-indigo-600 font-bold animate-pulse">Sedang menyiapkan halaman PDF untuk dicetak...</p>
                    <p class="text-xs text-gray-500">Mohon tunggu sebentar.</p>
                </div>
            </template>

        </template>

    </div>
</template>

<style scoped>
@media print {
    @page { size: A4; margin: 0; }
    body { background-color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .page-container { width: 210mm; min-height: 297mm; height: 297mm; padding: 15mm; margin: 0; box-shadow: none; page-break-after: always; position: relative; }
    .page-container:last-child { page-break-after: auto; }
    .break-before-page { page-break-before: always; margin-top: 0 !important; }
    .no-print { display: none; }
}
@media screen {
    .page-container { width: 210mm; min-height: 297mm; margin-bottom: 2rem; }
}
</style>