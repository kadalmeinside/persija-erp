<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BookOpenIcon, ChevronDownIcon, MagnifyingGlassIcon, ShieldCheckIcon, UserGroupIcon, BanknotesIcon, ClipboardDocumentListIcon, LifebuoyIcon, WrenchScrewdriverIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';

const openTopic = ref(null);
const searchQuery = ref('');
const toggleTopic = (id) => { openTopic.value = openTopic.value === id ? null : id; };
const modules = [
    { title: 'Dashboard & navigasi', icon: CalendarDaysIcon, color: 'text-indigo-600', topics: [
        { id: 'dashboard', title: 'Dashboard personal dan Direktur', steps: ['Dashboard menampilkan ringkasan absensi hari ini, saldo cuti, task, dan jumlah approval yang ditujukan kepada user.', 'Role <strong>Direktur</strong> mendapat kartu tambahan: task jatuh tempo hari ini, task overdue, karyawan yang sedang cuti, dan karyawan aktif yang belum absen.', 'Ringkasan tersebut adalah informasi, bukan pengganti pemeriksaan otorisasi pada halaman transaksi.'] },
        { id: 'calendar', title: 'Kalender dan hari libur', steps: ['Gunakan <strong>ESS &gt; Kalender</strong> untuk melihat event perusahaan.', 'Admin dapat mengelola hari libur melalui <strong>ESS &gt; Hari Libur</strong>. Perhitungan durasi cuti mengecualikan akhir pekan dan hari libur yang tersimpan.'] },
    ] },
    { title: 'Absensi & keamanan mobile', icon: ShieldCheckIcon, color: 'text-green-600', topics: [
        { id: 'attendance', title: 'Clock in/out dengan challenge', steps: ['Buka <strong>Personal &gt; Live Absensi</strong> dan izinkan kamera serta lokasi.', 'Aplikasi mobile harus meminta challenge terlebih dahulu. Challenge berlaku 5 menit, terikat pada aksi, dan hanya dapat digunakan sekali.', 'Kirim challenge, GPS, foto, request_id, dan nonce saat clock in/out. Dinas luar tetap mengirim lokasi dan catatan, tetapi melewati validasi radius kantor sesuai aturan.', 'Descriptor wajah diproses dan dicocokkan di perangkat mobile; backend menyimpan descriptor profile dan belum melakukan matching server-side.'] },
        { id: 'pin', title: 'PIN untuk approval', steps: ['PIN enam digit dipakai saat memproses approval dan diverifikasi terhadap PIN user yang sedang login.', 'Kelola PIN melalui menu <strong>Admin &gt; PIN</strong>. Jangan mengirim atau menyimpan PIN dalam log aplikasi.'] },
    ] },
    { title: 'Cuti & approval center', icon: UserGroupIcon, color: 'text-purple-600', topics: [
        { id: 'leave', title: 'Mengajukan dan membatalkan cuti', steps: ['Buka <strong>Personal &gt; Cuti Saya</strong>, pilih jenis dan rentang tanggal, lalu lengkapi keterangan serta lampiran jika diwajibkan.', 'Sistem menghitung hari kerja, mengecek overlap, saldo, aturan tanggal mundur, dan aturan jenis kelamin.', 'Saldo non-unlimited dicadangkan secara atomic ketika pengajuan dibuat. Pembatalan hanya dapat dilakukan oleh pemilik sebelum tanggal mulai dan hanya sekali. Jenis unlimited tidak dikurangi sebagai angka.'] },
        { id: 'approval-center', title: 'Approval Center terpadu', steps: ['Buka <strong>Approval Center</strong> untuk melihat process yang target employee-nya adalah Anda; role saja tidak otomatis memberi hak approval.', 'Timeline menampilkan setiap level, target employee, actor aktual, dan label aksi dari rule. Dokumen dapat berupa <strong>Pengajuan, Cuti, Pinjaman, atau Invoice</strong>.', 'Proses hanya bisa diproses saat status step <strong>Pending</strong>. Masukkan status Approved/Rejected, PIN enam digit, dan catatan opsional. Penolakan cuti mengembalikan saldo non-unlimited secara atomic.'] },
        { id: 'workflow', title: 'Mengatur aturan workflow', steps: ['Admin dapat mengelola <strong>ESS &gt; Approval Rules</strong>. Aturan menentukan dokumen, urutan level, target employee, dan label aksi.', 'Perubahan rule tidak boleh dipakai untuk menganggap role sebagai approver. Periksa target employee dan process yang dihasilkan setelah rule disimpan.'] },
    ] },
    { title: 'Pengajuan, settlement & finance', icon: BanknotesIcon, color: 'text-amber-600', topics: [
        { id: 'pengajuan', title: 'Pengajuan dana dan pembayaran', steps: ['Buat pengajuan melalui <strong>ESS &gt; Pengajuan</strong>. Helper form mengambil program, akun, pajak, dan saldo budget dari server.', 'Pantau approval pada tab approval, lalu Finance memproses pembayaran melalui jadwal pembayaran. Status transaksi dan aturan periode tetap berlaku.', 'Setelah pengeluaran, buat <strong>Settlement</strong> pada pengajuan terkait dan unggah bukti. Finance dapat memverifikasi atau menolak laporan.'] },
        { id: 'finance', title: 'Budget, GL, invoice dan periode', steps: ['Finance mengelola budget, chart of accounts, kas/bank, vendor, pelanggan, invoice, pajak, jurnal, aset, dan transfer internal.', 'Tanggal jurnal, pembayaran, dan aktivitas terkait tunduk pada <strong>period lock</strong>. Penutupan periode dilakukan dari <strong>Finance &gt; Period Closings</strong>; menu accounting-periods lama masih tersedia untuk kompatibilitas dan ditandai deprecated di route.'] },
    ] },
    { title: 'Task & IT Support', icon: ClipboardDocumentListIcon, color: 'text-blue-600', topics: [
        { id: 'tasks', title: 'Task dan delegasi', steps: ['Buat task dari <strong>Personal &gt; Manajemen Tugas</strong> dengan judul, prioritas, deadline, dan assignee.', 'Karyawan dapat memperbarui status To Do, In Progress, Review, atau Done sesuai akses. Direktur dapat mendelegasikan task kepada karyawan lain; akses bukan ditentukan hanya dari tampilan Kanban.', 'Task yang selesai dapat diarsipkan dan dilihat kembali pada riwayat.'] },
        { id: 'tickets', title: 'Tiket IT', steps: ['Buat tiket melalui <strong>Personal &gt; Tiket Saya</strong> dan sertakan detail masalah serta lampiran bila diperlukan.', 'Tambahkan komentar pada tiket untuk menjaga riwayat komunikasi. Pantau perubahan status sampai masalah selesai.'] },
    ] },
    { title: 'Master data, payroll & utilitas', icon: WrenchScrewdriverIcon, color: 'text-gray-600', topics: [
        { id: 'master', title: 'Master data dan payroll', steps: ['HR mengelola karyawan, lokasi kantor, jenis cuti, event perusahaan, payroll, pinjaman, dan riwayat karier.', 'Finance mengelola master departemen, vendor, pelanggan, akun GL, tipe pajak, periode anggaran, program kerja, dan posisi anggaran.', 'Payroll dan pinjaman mengikuti status serta service backend; jangan mengubah data cicilan atau periode secara langsung di database.'] },
        { id: 'utilities', title: 'PDF dan image tools', steps: ['Gunakan <strong>Utilitas &gt; PDF Tools</strong> untuk merge, split, konversi gambar, dan konversi Word ke PDF.', 'Gunakan <strong>Utilitas &gt; Image Tools</strong> untuk kompresi atau upscale gambar sebelum diunggah ke transaksi.'] },
    ] },
];
const filteredModules = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return modules;
    return modules.map(module => ({ ...module, topics: module.topics.filter(topic => `${topic.title} ${topic.steps.join(' ')}`.toLowerCase().includes(query)) })).filter(module => module.topics.length);
});
</script>

<template>
    <Head title="Dokumentasi Sistem" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Pusat Bantuan & Dokumentasi</h2></template>
        <div class="pb-12 pt-4"><div class="max-w-7xl mx-auto"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 pb-6 border-b dark:border-gray-700"><div class="flex items-center gap-4"><BookOpenIcon class="h-8 w-8 text-indigo-600" /><div><h3 class="text-2xl font-bold dark:text-gray-100">Panduan Penggunaan Sistem</h3><p class="text-gray-500">Panduan ini mengikuti menu dan alur pada implementasi admin saat ini.</p></div></div><div class="relative w-full md:w-80"><MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" /><input v-model="searchQuery" type="text" placeholder="Cari panduan..." class="w-full pl-10 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" /></div></div>
            <div v-if="!filteredModules.length" class="text-center py-12 text-gray-500">Panduan tidak ditemukan untuk "{{ searchQuery }}".</div>
            <div v-else class="space-y-6"><section v-for="module in filteredModules" :key="module.title" class="border rounded-xl overflow-hidden dark:border-gray-700"><div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex items-center gap-3"><component :is="module.icon" class="h-6 w-6" :class="module.color" /><h4 class="font-bold dark:text-gray-100">{{ module.title }}</h4></div><div class="divide-y dark:divide-gray-700"><div v-for="topic in module.topics" :key="topic.id"><button @click="toggleTopic(topic.id)" class="w-full px-6 py-4 flex justify-between text-left hover:bg-gray-50 dark:hover:bg-gray-700/50"><span class="font-medium dark:text-gray-200">{{ topic.title }}</span><ChevronDownIcon class="h-5 w-5 transition-transform" :class="openTopic === topic.id ? 'rotate-180 text-indigo-600' : 'text-gray-400'" /></button><div v-show="openTopic === topic.id" class="px-6 py-5 bg-gray-50 dark:bg-gray-900/50"><ol class="list-decimal ml-5 space-y-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300"><li v-for="(step, index) in topic.steps" :key="index" v-html="step"></li></ol></div></div></div></section></div>
        </div></div></div>
    </AuthenticatedLayout>
</template>
