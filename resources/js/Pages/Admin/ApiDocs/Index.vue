<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BookOpenIcon, KeyIcon, MapPinIcon, CalendarDaysIcon, CodeBracketIcon, ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const openEndpoint = ref(null);

const toggleEndpoint = (id) => {
    openEndpoint.value = openEndpoint.value === id ? null : id;
};

const apis = [
    {
        title: 'Authentication Module',
        icon: KeyIcon,
        color: 'text-blue-600',
        bg: 'bg-blue-50',
        endpoints: [
            {
                id: 'login',
                method: 'POST',
                url: '/api/v1/login',
                desc: 'Login to get Sanctum token',
                body: "{\n  \"email\": \"user@persija.id\",\n  \"password\": \"password123\"\n}",
                response: "{\n  \"data\": {\n    \"token\": \"1|xyz123...\",\n    \"user\": {\n      \"id\": 1,\n      \"name\": \"John Doe\",\n      \"email\": \"user@persija.id\",\n      \"role\": \"Staf\",\n      \"karyawan\": {\n        \"nip\": \"12345\",\n        \"nama_lengkap\": \"John Doe\",\n        \"jabatan\": \"Staff IT\",\n        \"departemen\": \"IT\"\n      }\n    }\n  }\n}"
            },
            {
                id: 'profile',
                method: 'GET',
                url: '/api/v1/user',
                desc: 'Get logged in user profile (incl. Geofence data)',
                headers: "Authorization: Bearer {token}",
                response: "{\n  \"data\": {\n    \"id\": 1,\n    \"name\": \"John Doe\",\n    \"email\": \"user@persija.id\",\n    \"role\": \"Staf\",\n    \"karyawan\": {\n      \"nip\": \"123\",\n      \"is_strict_location\": true,\n      \"lokasi_kantor\": {\n        \"nama\": \"HQ\",\n        \"latitude\": -6.2088,\n        \"longitude\": 106.8456,\n        \"radius\": 50\n      }\n    }\n  }\n}"
            },
            {
                id: 'logout',
                method: 'POST',
                url: '/api/v1/logout',
                desc: 'Revoke token',
                headers: "Authorization: Bearer {token}",
                response: "{\n  \"message\": \"Successfully logged out\"\n}"
            },
            {
                id: 'register_face',
                method: 'POST',
                url: '/api/v1/user/register-face',
                desc: 'Register a face descriptor once for the authenticated employee.',
                headers: "Authorization: ****** application/json",
                body: "{\n  \"face_descriptor\": \"<json-string>\"\n}",
                response: "{\n  \"message\": \"Wajah berhasil didaftarkan.\",\n  \"user\": { }\n}"
            },
            {
                id: 'update_password',
                method: 'POST',
                url: '/api/v1/update-password',
                desc: 'Update the authenticated user password.',
                headers: "Authorization: ****** application/json",
                body: "{\n  \"current_password\": \"old-password\",\n  \"new_password\": \"new-password\",\n  \"new_password_confirmation\": \"new-password\"\n}",
                response: "{\n  \"message\": \"Password berhasil diperbarui.\"\n}"
            },
        ]
    },
    {
        title: 'Absensi (Attendance) Module',
        icon: MapPinIcon,
        color: 'text-green-600',
        bg: 'bg-green-50',
        endpoints: [
            {
                id: 'absensi_today',
                method: 'GET',
                url: '/api/v1/absensi/today',
                desc: "Check today's attendance status",
                headers: "Authorization: Bearer {token}",
                response: "{\n  \"data\": {\n    \"clock_in\": \"08:00:00\",\n    \"clock_out\": null,\n    \"status\": \"Hadir\"\n  }\n}"
            },
            {
                id: 'absensi_in',
                method: 'POST',
                url: '/api/v1/absensi/clock-in',
                desc: 'Clock in (Requires GPS & Photo)',
                headers: "Authorization: Bearer {token}\nContent-Type: multipart/form-data",
                body: "latitude: -6.2088\nlongitude: 106.8456\nphoto: (File/Image)\nis_dinas_luar: 1 (Optional)\ncatatan: Meeting client (Optional)",
                response: "{\n  \"message\": \"Berhasil absen masuk.\",\n  \"data\": {\n    \"id\": 10,\n    \"clock_in\": \"08:00:00\"\n  }\n}"
            },
            {
                id: 'absensi_out',
                method: 'POST',
                url: '/api/v1/absensi/clock-out',
                desc: 'Clock out (Requires GPS & Photo)',
                headers: "Authorization: Bearer {token}\nContent-Type: multipart/form-data",
                body: "latitude: -6.2088\nlongitude: 106.8456\nphoto: (File/Image)",
                response: "{\n  \"message\": \"Berhasil absen pulang.\",\n  \"data\": {\n    \"id\": 10,\n    \"clock_out\": \"17:00:00\"\n  }\n}"
            },
            {
                id: 'absensi_history',
                method: 'GET',
                url: '/api/v1/absensi/history?month=09&year=2026',
                desc: 'Get attendance history',
                headers: "Authorization: Bearer {token}",
                response: "{\n  \"data\": [\n    {\n      \"id\": 10,\n      \"date\": \"2026-09-25\",\n      \"clock_in\": \"08:00:00\",\n      \"clock_out\": \"17:00:00\",\n      \"status\": \"Hadir\"\n    }\n  ]\n}"
            },
            {
                id: 'absensi_challenge',
                method: 'POST',
                url: '/api/v1/absensi/challenge',
                desc: 'Create a five-minute, single-use challenge before clock-in/out.',
                headers: "Authorization: ****** application/json",
                body: "{\n  \"action\": \"clock-in\",\n  \"security_metadata\": { \"platform\": \"android\", \"app_version\": \"1.0.0\" }\n}",
                response: "{\n  \"data\": {\n    \"action\": \"clock-in\",\n    \"request_id\": \"<uuid>\",\n    \"nonce\": \"<64-character nonce>\",\n    \"expires_at\": \"<iso8601>\"\n  }\n}"
            },
        ]
    },
    {
        title: 'Cuti (Leave) Module',
        icon: CalendarDaysIcon,
        color: 'text-purple-600',
        bg: 'bg-purple-50',
        endpoints: [
            {
                id: 'cuti_jenis',
                method: 'GET',
                url: '/api/v1/cuti/jenis',
                desc: 'Get available leave types for dropdown',
                headers: "Authorization: Bearer {token}",
                response: "{\n  \"data\": [\n    {\n      \"id\": 1,\n      \"nama_cuti\": \"Cuti Tahunan\",\n      \"kuota_default\": 12,\n      \"wajib_lampiran\": false\n    },\n    {\n      \"id\": 2,\n      \"nama_cuti\": \"Cuti Sakit\",\n      \"kuota_default\": 12,\n      \"wajib_lampiran\": true\n    }\n  ]\n}"
            },
            {
                id: 'cuti_balances',
                method: 'GET',
                url: '/api/v1/cuti/balances',
                desc: 'Get leave balances for current year',
                headers: "Authorization: Bearer {token}",
                response: "{\n  \"data\": [\n    {\n      \"id\": 1,\n      \"jenis_cuti_id\": 1,\n      \"jenis_cuti\": \"Cuti Tahunan\",\n      \"saldo_awal\": 12,\n      \"saldo_terpakai\": 2,\n      \"saldo_akhir\": 10\n    }\n  ]\n}"
            },
            {
                id: 'cuti_requests',
                method: 'GET',
                url: '/api/v1/cuti/requests',
                desc: 'Get my leave request history',
                headers: "Authorization: Bearer {token}",
                response: "{\n  \"data\": [\n    {\n      \"id\": 5,\n      \"jenis_cuti\": \"Cuti Tahunan\",\n      \"tgl_mulai\": \"2026-10-01\",\n      \"tgl_selesai\": \"2026-10-02\",\n      \"jumlah_hari\": 2,\n      \"alasan\": \"Urusan keluarga\",\n      \"status\": \"Pending\",\n      \"lampiran\": null,\n      \"created_at\": \"2026-09-27 08:00:00\"\n    }\n  ]\n}"
            },
            {
                id: 'cuti_submit',
                method: 'POST',
                url: '/api/v1/cuti/request',
                desc: 'Submit a new leave request',
                headers: "Authorization: Bearer {token}\nContent-Type: multipart/form-data",
                body: "jenis_cuti_id: 1               (Integer, Required)\ntgl_mulai: 2026-10-01          (String YYYY-MM-DD, Required)\ntgl_selesai: 2026-10-02        (String YYYY-MM-DD, Required)\nketerangan: Sakit (Demam)      (String max 500, Required)\nattachment: (File jpg/png/pdf) (Optional – Wajib jika wajib_lampiran: true)",
                response: "{\n  \"message\": \"Pengajuan cuti berhasil dibuat\",\n  \"data\": {\n    \"id\": 5,\n    \"status\": \"Pending\"\n  }\n}"
            },
            {
                id: 'cuti_cancel',
                method: 'POST',
                url: '/api/v1/cuti/cancel/{id}',
                desc: 'Cancel the user’s own request once, before its start date.',
                headers: "Authorization: ****** application/json",
                body: "{\n  \"reason\": \"Perubahan rencana\"\n}",
                response: "{\n  \"message\": \"Pengajuan cuti berhasil dibatalkan.\"\n}"
            },
        ]
    },
    {
        title: 'Approval Module (Manager / HR Only)',
        icon: CalendarDaysIcon,
        color: 'text-amber-600',
        bg: 'bg-amber-50',
        endpoints: [
            {
                id: 'cuti_approvals',
                method: 'GET',
                url: '/api/v1/cuti/approvals',
                desc: 'Get pending approvals (Role: Manajer / HR)',
                headers: "Authorization: Bearer {token}",
                response: "{\n  \"data\": [\n    {\n      \"id\": 5,\n      \"nama_karyawan\": \"Jane Smith\",\n      \"nip\": \"54321\",\n      \"departemen\": \"IT\",\n      \"jenis_cuti\": \"Cuti Tahunan\",\n      \"tgl_mulai\": \"2026-10-01\",\n      \"tgl_selesai\": \"2026-10-02\",\n      \"jumlah_hari\": 2,\n      \"alasan\": \"Urusan keluarga\",\n      \"lampiran\": null,\n      \"status\": \"Pending\",\n      \"created_at\": \"2026-09-27 08:00:00\"\n    }\n  ]\n}"
            },
            {
                id: 'cuti_approve',
                method: 'POST',
                url: '/api/v1/cuti/approve/{id}',
                desc: 'Approve or Reject a leave request (Role: Manajer / HR)',
                headers: "Authorization: Bearer {token}\nContent-Type: application/json",
                body: "{\n  \"status\": \"Approved\",\n  \"catatan\": \"Disetujui, koordinasi dengan tim.\"\n}\n\n// status hanya boleh: \"Approved\" atau \"Rejected\"",
                response: "{\n  \"message\": \"Pengajuan berhasil approved\"\n}\n\n// Error: { \"message\": \"Pengajuan ini sudah diproses.\" } (422)\n// Error: { \"message\": \"Anda tidak memiliki akses.\" } (403)"
            },
        ]
    },
    {
        title: 'Dashboard & Unified Approval Center',
        icon: BookOpenIcon,
        color: 'text-amber-600',
        bg: 'bg-amber-50',
        endpoints: [
            {
                id: 'dashboard_home',
                method: 'GET',
                url: '/api/v1/dashboard/home',
                desc: 'BFF dashboard with attendance, leave balance, tasks, approvals, and director overview.',
                headers: "Authorization: ****** application/json",
                response: "{\n  \"data\": {\n    \"user\": { },\n    \"attendance_today\": { },\n    \"leave_balance\": { },\n    \"tasks\": { \"pending_approvals\": 0, \"is_approver\": false },\n    \"director_overview\": { }\n  }\n}"
            },
            {
                id: 'approval_index',
                method: 'GET',
                url: '/api/v1/approvals?status=all&per_page=20',
                desc: 'List approval processes assigned to the employee in the token. Default status is Pending.',
                headers: "Authorization: ****** application/json",
                response: "{ \"data\": [], \"meta\": { \"current_page\": 1, \"last_page\": 1, \"per_page\": 20, \"total\": 0 } }"
            },
            {
                id: 'approval_show',
                method: 'GET',
                url: '/api/v1/approvals/{approval}',
                desc: 'Show an approval detail and the complete document timeline.',
                headers: "Authorization: ****** application/json",
                response: "{ \"data\": { \"approval\": { }, \"timeline\": [] } }"
            },
            {
                id: 'approval_action',
                method: 'POST',
                url: '/api/v1/approvals/{approval}/action',
                desc: 'Approve or reject a Pending step only when the token employee is its target.',
                headers: "Authorization: ****** application/json",
                body: "{\n  \"status\": \"Approved\",\n  \"pin\": \"123456\",\n  \"catatan\": \"Diproses\"\n}",
                response: "{ \"message\": \"Approval berhasil diproses.\" }"
            }
        ]
    },
    {
        title: 'Task Management, Calendar & Employees',
        icon: CodeBracketIcon,
        color: 'text-indigo-600',
        bg: 'bg-indigo-50',
        endpoints: [
            {
                id: 'tasks_index',
                method: 'GET',
                url: '/api/v1/tasks',
                desc: 'Get active tasks assigned to or created by the authenticated user.',
                headers: "Authorization: ****** application/json",
                response: "{ \"success\": true, \"data\": [] }"
            },
            {
                id: 'tasks_store',
                method: 'POST',
                url: '/api/v1/tasks',
                desc: 'Create a task. Assignment to another employee is restricted to permitted roles.',
                headers: "Authorization: ****** application/json",
                body: "{\n  \"title\": \"Review laporan\",\n  \"description\": \"...\",\n  \"priority\": \"Medium\",\n  \"due_date\": \"2026-10-10\",\n  \"id_karyawan_assignee\": 12\n}",
                response: "{ \"success\": true, \"message\": \"Tugas berhasil dibuat\", \"data\": { } }"
            },
            {
                id: 'tasks_history',
                method: 'GET',
                url: '/api/v1/tasks/history',
                desc: 'Get paginated task history including archived tasks.',
                headers: "Authorization: ****** application/json",
                response: "{ \"success\": true, \"data\": { \"data\": [], \"meta\": { } } }"
            },
            {
                id: 'tasks_status',
                method: 'POST',
                url: '/api/v1/tasks/{id}/status',
                desc: 'Change task status or archive a task.',
                headers: "Authorization: ****** application/json",
                body: "{ \"status\": \"In Progress\", \"archive\": false }",
                response: "{ \"success\": true, \"message\": \"Status tugas berhasil diperbarui\", \"data\": { } }"
            },
            {
                id: 'calendar_index',
                method: 'GET',
                url: '/api/v1/calendar',
                desc: 'Get company calendar events for mobile.',
                headers: "Authorization: ****** application/json",
                response: "{ \"data\": [] }"
            },
            {
                id: 'employees_index',
                method: 'GET',
                url: '/api/v1/karyawan',
                desc: 'Get employees available to the authenticated mobile user.',
                headers: "Authorization: ****** application/json",
                response: "{ \"data\": [] }"
            }
        ]
    }
];

const getMethodColor = (method) => {
    switch (method) {
        case 'GET': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'POST': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'PUT':
        case 'PATCH': return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'DELETE': return 'bg-red-100 text-red-700 border-red-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};
</script>

<template>
    <Head title="API Documentation" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <CodeBracketIcon class="w-6 h-6 text-gray-600" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mobile API Documentation</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900 border-b border-gray-200">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-indigo-50 rounded-lg shrink-0">
                                <BookOpenIcon class="w-6 h-6 text-indigo-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Developer Guide (Phase 1)</h3>
                                <p class="text-gray-600 mt-1">This API is designed specifically for Native Android & iOS applications to enforce hardware-based GPS locations.</p>

                                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <h4 class="font-semibold text-gray-700 mb-2">Base Configurations</h4>
                                        <ul class="space-y-1 text-sm text-gray-600">
                                            <li><span class="font-medium">Base URL:</span> <code class="bg-gray-200 px-1 py-0.5 rounded text-indigo-700">https://your-domain.com/api/v1</code></li>
                                            <li><span class="font-medium">Auth:</span> <code class="bg-gray-200 px-1 py-0.5 rounded text-indigo-700">Bearer {token}</code></li>
                                            <li><span class="font-medium">Accept:</span> <code class="bg-gray-200 px-1 py-0.5 rounded text-indigo-700">application/json</code></li>
                                        </ul>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <h4 class="font-semibold text-gray-700 mb-2">Success Response (JSend)</h4>
                                        <ul class="space-y-1 text-sm text-gray-600">
                                            <li><span class="font-medium text-emerald-600">200 OK:</span> <code>{"data": {...}}</code></li>
                                            <li><span class="font-medium text-emerald-600">201 Created:</span> <code>{"message":"..","data":{}}</code></li>
                                        </ul>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <h4 class="font-semibold text-gray-700 mb-2">Error Responses</h4>
                                        <ul class="space-y-1 text-sm text-gray-600">
                                            <li><span class="font-medium text-amber-600">401:</span> Token expired → redirect Login</li>
                                            <li><span class="font-medium text-red-600">403:</span> Akses ditolak (role)</li>
                                            <li><span class="font-medium text-red-600">404:</span> Data tidak ditemukan</li>
                                            <li><span class="font-medium text-red-600">422:</span> <code>{"errors":{"field":[...]}}</code></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Modules -->
                <div class="grid grid-cols-1 gap-6">
                    <div v-for="(module, index) in apis" :key="index" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-4 border-b border-gray-100 flex items-center gap-3" :class="module.bg">
                            <component :is="module.icon" class="w-5 h-5" :class="module.color" />
                            <h3 class="font-bold text-gray-900">{{ module.title }}</h3>
                        </div>
                        <div class="p-0">
                            <ul class="divide-y divide-gray-100">
                                <li v-for="(ep, i) in module.endpoints" :key="i" class="p-0 hover:bg-gray-50 transition-colors">
                                    <div @click="toggleEndpoint(ep.id)" class="p-4 cursor-pointer flex justify-between items-center w-full">
                                        <div>
                                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-2">
                                                <span class="px-2.5 py-1 text-xs font-bold rounded border shrink-0 w-max" :class="getMethodColor(ep.method)">
                                                    {{ ep.method }}
                                                </span>
                                                <code class="text-sm font-semibold text-gray-800 break-all">{{ ep.url }}</code>
                                            </div>
                                            <p class="text-sm text-gray-600 sm:ml-[72px]">{{ ep.desc }}</p>
                                        </div>
                                        <ChevronDownIcon v-if="openEndpoint !== ep.id" class="w-5 h-5 text-gray-400" />
                                        <ChevronUpIcon v-else class="w-5 h-5 text-gray-400" />
                                    </div>

                                    <!-- Accordion Detail (Swagger Style) -->
                                    <div v-if="openEndpoint === ep.id" class="p-4 bg-gray-50 border-t border-gray-200 text-sm">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                            <!-- Request Section -->
                                            <div>
                                                <h4 class="font-bold text-gray-700 mb-2">Request</h4>
                                                <div v-if="ep.headers" class="mb-3">
                                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Headers</span>
                                                    <pre class="mt-1 bg-gray-800 text-green-400 p-3 rounded-md overflow-x-auto"><code>{{ ep.headers }}</code></pre>
                                                </div>
                                                <div v-if="ep.body">
                                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Payload / Body</span>
                                                    <pre class="mt-1 bg-gray-800 text-blue-300 p-3 rounded-md overflow-x-auto"><code>{{ ep.body }}</code></pre>
                                                </div>
                                                <div v-if="!ep.body && !ep.headers" class="text-gray-500 italic">No additional payload required.</div>
                                            </div>

                                            <!-- Response Section -->
                                            <div>
                                                <h4 class="font-bold text-gray-700 mb-2">Success Response</h4>
                                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Format (JSON)</span>
                                                <pre class="mt-1 bg-gray-800 text-emerald-400 p-3 rounded-md overflow-x-auto"><code>{{ ep.response }}</code></pre>
                                            </div>

                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
