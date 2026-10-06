<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    BookOpenIcon,
    CalendarDaysIcon,
    ChartBarIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    CodeBracketIcon,
    KeyIcon,
    MapPinIcon,
    CheckBadgeIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';

const openEndpoint = ref(null);
const toggleEndpoint = (id) => {
    openEndpoint.value = openEndpoint.value === id ? null : id;
};

const auth = 'Authorization: Bearer <sanctum-token>\nAccept: application/json';
const apis = [
    {
        title: 'Authentication & Profile',
        icon: KeyIcon,
        color: 'text-blue-600',
        bg: 'bg-blue-50',
        endpoints: [
            { id: 'login', method: 'POST', url: '/api/v1/login', desc: 'Login mobile. Semua token lama user dicabut.', body: '{"email":"user@persija.id","password":"password123"}', response: '{"data":{"token":"1|...","user":{}}}' },
            { id: 'logout', method: 'POST', url: '/api/v1/logout', desc: 'Cabut token yang sedang digunakan.', headers: auth, response: '{"message":"Successfully logged out"}' },
            { id: 'profile', method: 'GET', url: '/api/v1/user', desc: 'Ambil profil user dan data karyawan.', headers: auth, response: '{"data":{"id":1,"name":"...","karyawan":{}}}' },
            { id: 'register-face', method: 'POST', url: '/api/v1/user/register-face', desc: 'Simpan descriptor wajah sekali untuk user.', headers: auth, body: '{"face_descriptor":"<json-string>"}', response: '{"message":"Wajah berhasil didaftarkan.","user":{}}' },
            { id: 'update-password', method: 'POST', url: '/api/v1/update-password', desc: 'Perbarui password user.', headers: auth, body: '{"current_password":"...","new_password":"...","new_password_confirmation":"..."}', response: '{"message":"Password berhasil diperbarui."}' },
        ],
    },
    {
        title: 'Dashboard & Approval Center',
        icon: ChartBarIcon,
        color: 'text-amber-600',
        bg: 'bg-amber-50',
        endpoints: [
            { id: 'dashboard', method: 'GET', url: '/api/v1/dashboard/home', desc: 'BFF dashboard: absensi, saldo cuti, approval, dan director_overview untuk role Direktur.', headers: auth, response: '{"data":{"user":{},"attendance_today":{},"leave_balance":{},"tasks":{},"director_overview":{}}}' },
            { id: 'approvals', method: 'GET', url: '/api/v1/approvals?status=all&per_page=20', desc: 'Daftar process approval yang ditujukan kepada employee pada token. Default status Pending.', headers: auth, response: '{"data":[],"meta":{"current_page":1,"last_page":1,"per_page":20,"total":0}}' },
            { id: 'approval-detail', method: 'GET', url: '/api/v1/approvals/{approval}', desc: 'Detail approval dan timeline seluruh level dokumen.', headers: auth, response: '{"data":{"approval":{},"timeline":[]}}' },
            { id: 'approval-action', method: 'POST', url: '/api/v1/approvals/{approval}/action', desc: 'Approve/reject hanya jika user adalah target step Pending.', headers: auth, body: '{"status":"Approved","pin":"123456","catatan":"Diproses"}', response: '{"message":"Approval berhasil diproses."}' },
        ],
    },
    {
        title: 'Absensi',
        icon: MapPinIcon,
        color: 'text-green-600',
        bg: 'bg-green-50',
        endpoints: [
            { id: 'attendance-today', method: 'GET', url: '/api/v1/absensi/today', desc: 'Status absensi hari ini.', headers: auth, response: '{"data":{"clock_in":"08:00:00","clock_out":null,"status":"Hadir"}}' },
            { id: 'attendance-challenge', method: 'POST', url: '/api/v1/absensi/challenge', desc: 'Buat challenge sekali pakai, berlaku 5 menit.', headers: auth, body: '{"action":"clock-in","security_metadata":{"platform":"android"}}', response: '{"data":{"action":"clock-in","request_id":"<uuid>","nonce":"<64-char>","expires_at":"<iso8601>"}}' },
            { id: 'clock-in', method: 'POST', url: '/api/v1/absensi/clock-in', desc: 'Clock in dengan challenge, GPS, dan foto.', headers: `${auth}\nContent-Type: multipart/form-data`, body: 'latitude, longitude, photo, request_id, nonce\nis_dinas_luar (optional), catatan (optional)', response: '{"message":"...","data":{"id":10,"clock_in":"08:00:00"}}' },
            { id: 'clock-out', method: 'POST', url: '/api/v1/absensi/clock-out', desc: 'Clock out dengan challenge, GPS, dan foto.', headers: `${auth}\nContent-Type: multipart/form-data`, body: 'latitude, longitude, photo, request_id, nonce', response: '{"message":"...","data":{"id":10,"clock_out":"17:00:00"}}' },
            { id: 'attendance-history', method: 'GET', url: '/api/v1/absensi/history?month=10&year=2026', desc: 'Riwayat absensi terpaginasikan 10 item per halaman.', headers: auth, response: '{"data":[],"links":{},"meta":{}}' },
        ],
    },
    {
        title: 'Cuti',
        icon: CalendarDaysIcon,
        color: 'text-purple-600',
        bg: 'bg-purple-50',
        endpoints: [
            { id: 'leave-types', method: 'GET', url: '/api/v1/cuti/jenis', desc: 'Jenis cuti dan aturan lampiran, mundur, gender, serta unlimited.', headers: auth, response: '{"data":[{"id":1,"nama_cuti":"Cuti Tahunan","is_unlimited":false}]}' },
            { id: 'leave-balances', method: 'GET', url: '/api/v1/cuti/balances', desc: 'Saldo cuti tahun berjalan. Unlimited harus ditampilkan sebagai Unlimited.', headers: auth, response: '{"data":[{"jenis_cuti":"Cuti Tahunan","saldo_akhir":10,"is_unlimited":false}]}' },
            { id: 'leave-requests', method: 'GET', url: '/api/v1/cuti/requests', desc: 'Riwayat pengajuan cuti user.', headers: auth, response: '{"data":[{"id":5,"status":"Pending"}]}' },
            { id: 'leave-request', method: 'POST', url: '/api/v1/cuti/request', desc: 'Ajukan cuti; hari kerja dan hari libur dihitung backend.', headers: `${auth}\nContent-Type: multipart/form-data`, body: 'jenis_cuti_id, tgl_mulai, tgl_selesai, keterangan\nattachment (optional; wajib sesuai jenis cuti)', response: '{"message":"Pengajuan cuti berhasil dibuat","data":{"id":5,"status":"Pending"}}' },
            { id: 'leave-cancel', method: 'POST', url: '/api/v1/cuti/cancel/{id}', desc: 'Batalkan pengajuan sendiri sebelum tanggal mulai.', headers: auth, body: '{"reason":"Perubahan rencana"}', response: '{"message":"..."}' },
            { id: 'leave-approvals', method: 'GET', url: '/api/v1/cuti/approvals', desc: 'Endpoint legacy approval cuti untuk kompatibilitas mobile.', headers: auth, response: '{"data":[]}' },
            { id: 'leave-approve', method: 'POST', url: '/api/v1/cuti/approve/{id}', desc: 'Endpoint legacy approve/reject cuti dengan PIN.', headers: auth, body: '{"status":"Approved","pin":"123456","catatan":"Disetujui"}', response: '{"message":"..."}' },
        ],
    },
    {
        title: 'Tasks, Calendar & Employees',
        icon: ClipboardDocumentListIcon,
        color: 'text-indigo-600',
        bg: 'bg-indigo-50',
        endpoints: [
            { id: 'tasks', method: 'GET', url: '/api/v1/tasks', desc: 'Task aktif yang dapat diakses user.', headers: auth, response: '{"success":true,"data":[]}' },
            { id: 'task-create', method: 'POST', url: '/api/v1/tasks', desc: 'Buat task; assignment ke employee lain dibatasi role.', headers: auth, body: '{"title":"...","description":"...","priority":"Medium","due_date":"2026-10-10","id_karyawan_assignee":1}', response: '{"success":true,"data":{}}' },
            { id: 'task-history', method: 'GET', url: '/api/v1/tasks/history', desc: 'Riwayat task aktif dan archived.', headers: auth, response: '{"success":true,"data":{}}' },
            { id: 'task-status', method: 'POST', url: '/api/v1/tasks/{id}/status', desc: 'Ubah status To Do, In Progress, Review, Done atau archive.', headers: auth, body: '{"status":"In Progress","archive":false}', response: '{"success":true,"data":{}}' },
            { id: 'calendar', method: 'GET', url: '/api/v1/calendar', desc: 'Event kalender untuk mobile.', headers: auth, response: '{"data":[]}' },
            { id: 'employees', method: 'GET', url: '/api/v1/karyawan', desc: 'Daftar karyawan yang tersedia untuk kebutuhan mobile.', headers: auth, response: '{"data":[]}' },
        ],
    },
];

const getMethodColor = (method) => ({
    GET: 'bg-green-100 text-green-700 border-green-200',
    POST: 'bg-blue-100 text-blue-700 border-blue-200',
}[method] ?? 'bg-gray-100 text-gray-700 border-gray-200');
</script>

<template>
    <Head title="Mobile API Documentation" />
    <AuthenticatedLayout>
        <template #header><div class="flex items-center gap-3"><CodeBracketIcon class="w-6 h-6 text-gray-600" /><h2 class="font-semibold text-xl text-gray-800 leading-tight">Mobile API Documentation</h2></div></template>
        <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-6">
                <div class="flex gap-4"><BookOpenIcon class="w-7 h-7 text-indigo-600 shrink-0" /><div>
                    <h3 class="text-lg font-bold">API v1 Native Mobile</h3>
                    <p class="text-gray-600 mt-1">Sumber dokumentasi ini adalah <code>routes/api.php</code>. Semua endpoint selain login memakai Sanctum.</p>
                    <div class="grid md:grid-cols-3 gap-3 mt-4 text-sm"><div class="bg-gray-50 p-3 rounded"><b>Base URL</b><br><code>/api/v1</code></div><div class="bg-gray-50 p-3 rounded"><b>Headers</b><br><code>Accept: application/json</code><br><code>Authorization: Bearer ...</code></div><div class="bg-gray-50 p-3 rounded"><b>Validasi</b><br>401 token, 403 akses, 422 input/proses.</div></div>
                </div></div>
            </div>
            <div class="grid gap-6"><div v-for="module in apis" :key="module.title" class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b flex items-center gap-3" :class="module.bg"><component :is="module.icon" class="w-5 h-5" :class="module.color" /><h3 class="font-bold">{{ module.title }}</h3></div>
                <ul class="divide-y"><li v-for="ep in module.endpoints" :key="ep.id">
                    <button @click="toggleEndpoint(ep.id)" class="w-full p-4 text-left flex justify-between items-center hover:bg-gray-50"><div><div class="flex flex-wrap items-center gap-3"><span class="px-2 py-1 text-xs font-bold rounded border" :class="getMethodColor(ep.method)">{{ ep.method }}</span><code class="text-sm font-semibold">{{ ep.url }}</code></div><p class="text-sm text-gray-600 mt-2">{{ ep.desc }}</p></div><ChevronUpIcon v-if="openEndpoint === ep.id" class="w-5 h-5" /><ChevronDownIcon v-else class="w-5 h-5 text-gray-400" /></button>
                    <div v-if="openEndpoint === ep.id" class="p-4 bg-gray-50 border-t grid md:grid-cols-2 gap-5 text-sm"><div><h4 class="font-bold mb-2">Request</h4><pre v-if="ep.headers" class="mb-3 bg-gray-800 text-green-300 p-3 rounded overflow-x-auto"><code>{{ ep.headers }}</code></pre><pre v-if="ep.body" class="bg-gray-800 text-blue-300 p-3 rounded overflow-x-auto"><code>{{ ep.body }}</code></pre><span v-if="!ep.headers && !ep.body" class="text-gray-500 italic">Tidak ada payload tambahan.</span></div><div><h4 class="font-bold mb-2">Success response</h4><pre class="bg-gray-800 text-emerald-300 p-3 rounded overflow-x-auto"><code>{{ ep.response }}</code></pre></div></div>
                </li></ul>
            </div></div>
        </div></div>
    </AuthenticatedLayout>
</template>
