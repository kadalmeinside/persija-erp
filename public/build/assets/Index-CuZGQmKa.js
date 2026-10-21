import{c as _,d as y,e as k,b as P,f as S,g as B,A as G}from"./AuthenticatedLayout-sM0XA7DB.js";import{c as s,d as e,m as t,b as c,u,h as z,w as v,e as i,F as m,x as f,g as h,s as g,C,t as r,f as p,r as E}from"./app-BgYeHMJj.js";import{r as O}from"./KeyIcon-tduLui_v.js";import{r as R}from"./MapPinIcon-BmvH8W1Q.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";import"./CheckCircleIcon-DVUFttKp.js";import"./XCircleIcon-Ds349VGy.js";import"./Toast-C-VPjsql.js";import"./XCircleIcon-DdKUgenE.js";import"./XMarkIcon-Bb7qwkyN.js";import"./Modal-Df_aL21K.js";import"./SecondaryButton-YFGk9Wbq.js";import"./DangerButton-DVLVwQO-.js";import"./sweetalert2.esm.all-DcO9kRa-.js";import"./ShieldCheckIcon-D5gdX_Vs.js";import"./CalendarIcon-DQoDKLmD.js";function M(b,o){return t(),s("svg",{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24","stroke-width":"1.5",stroke:"currentColor","aria-hidden":"true","data-slot":"icon"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"})])}function D(b,o){return t(),s("svg",{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24","stroke-width":"1.5",stroke:"currentColor","aria-hidden":"true","data-slot":"icon"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"m4.5 15.75 7.5-7.5 7.5 7.5"})])}const I={class:"flex items-center gap-3"},N={class:"pb-12 pt-4"},q={class:"max-w-7xl mx-auto"},H={class:"bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"},F={class:"p-6 text-gray-900 border-b border-gray-200"},L={class:"flex items-start gap-4"},U={class:"p-3 bg-indigo-50 rounded-lg shrink-0"},$={class:"grid grid-cols-1 gap-6"},J={class:"font-bold text-gray-900"},Y={class:"p-0"},V={class:"divide-y divide-gray-100"},K=["onClick"],W={class:"flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-2"},Q={class:"text-sm font-semibold text-gray-800 break-all"},Z={class:"text-sm text-gray-600 sm:ml-[72px]"},X={key:0,class:"p-4 bg-gray-50 border-t border-gray-200 text-sm"},ee={class:"grid grid-cols-1 md:grid-cols-2 gap-6"},ae={key:0,class:"mb-3"},ne={class:"mt-1 bg-gray-800 text-green-400 p-3 rounded-md overflow-x-auto"},te={key:1},se={class:"mt-1 bg-gray-800 text-blue-300 p-3 rounded-md overflow-x-auto"},ie={key:2,class:"text-gray-500 italic"},re={class:"mt-1 bg-gray-800 text-emerald-400 p-3 rounded-md overflow-x-auto"},Te={__name:"Index",setup(b){const o=E(null),x=d=>{o.value=o.value===d?null:d},w=[{title:"Authentication Module",icon:O,color:"text-blue-600",bg:"bg-blue-50",endpoints:[{id:"login",method:"POST",url:"/api/v1/login",desc:"Login to get Sanctum token",body:`{
  "email": "user@persija.id",
  "password": "password123"
}`,response:`{
  "data": {
    "token": "1|xyz123...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@persija.id",
      "role": "Staf",
      "karyawan": {
        "nip": "12345",
        "nama_lengkap": "John Doe",
        "jabatan": "Staff IT",
        "departemen": "IT"
      }
    }
  }
}`},{id:"profile",method:"GET",url:"/api/v1/user",desc:"Get logged in user profile (incl. Geofence data)",headers:"Authorization: Bearer {token}",response:`{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@persija.id",
    "role": "Staf",
    "karyawan": {
      "nip": "123",
      "is_strict_location": true,
      "lokasi_kantor": {
        "nama": "HQ",
        "latitude": -6.2088,
        "longitude": 106.8456,
        "radius": 50
      }
    }
  }
}`},{id:"logout",method:"POST",url:"/api/v1/logout",desc:"Revoke token",headers:"Authorization: Bearer {token}",response:`{
  "message": "Successfully logged out"
}`},{id:"register_face",method:"POST",url:"/api/v1/user/register-face",desc:"Register a face descriptor once for the authenticated employee.",headers:"Authorization: ****** application/json",body:`{
  "face_descriptor": "<json-string>"
}`,response:`{
  "message": "Wajah berhasil didaftarkan.",
  "user": { }
}`},{id:"update_password",method:"POST",url:"/api/v1/update-password",desc:"Update the authenticated user password.",headers:"Authorization: ****** application/json",body:`{
  "current_password": "old-password",
  "new_password": "new-password",
  "new_password_confirmation": "new-password"
}`,response:`{
  "message": "Password berhasil diperbarui."
}`}]},{title:"Absensi (Attendance) Module",icon:R,color:"text-green-600",bg:"bg-green-50",endpoints:[{id:"absensi_today",method:"GET",url:"/api/v1/absensi/today",desc:"Check today's attendance status",headers:"Authorization: Bearer {token}",response:`{
  "data": {
    "clock_in": "08:00:00",
    "clock_out": null,
    "status": "Hadir"
  }
}`},{id:"absensi_absent_today",method:"GET",url:"/api/v1/absensi/absent-today",desc:"Get list of employees who have not clocked in today (HR / Management view).",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "data": [
    {
      "id": 12,
      "nama_lengkap": "Budi Santoso",
      "departemen": "Finance"
    }
  ]
}`},{id:"absensi_in",method:"POST",url:"/api/v1/absensi/clock-in",desc:"Clock in (Requires GPS & Photo)",headers:`Authorization: Bearer {token}
Content-Type: multipart/form-data`,body:`latitude: -6.2088
longitude: 106.8456
photo: (File/Image)
is_dinas_luar: 1 (Optional)
catatan: Meeting client (Optional)`,response:`{
  "message": "Berhasil absen masuk.",
  "data": {
    "id": 10,
    "clock_in": "08:00:00"
  }
}`},{id:"absensi_out",method:"POST",url:"/api/v1/absensi/clock-out",desc:"Clock out (Requires GPS & Photo)",headers:`Authorization: Bearer {token}
Content-Type: multipart/form-data`,body:`latitude: -6.2088
longitude: 106.8456
photo: (File/Image)`,response:`{
  "message": "Berhasil absen pulang.",
  "data": {
    "id": 10,
    "clock_out": "17:00:00"
  }
}`},{id:"absensi_history",method:"GET",url:"/api/v1/absensi/history?month=09&year=2026",desc:"Get attendance history",headers:"Authorization: Bearer {token}",response:`{
  "data": [
    {
      "id": 10,
      "date": "2026-09-25",
      "clock_in": "08:00:00",
      "clock_out": "17:00:00",
      "status": "Hadir"
    }
  ]
}`},{id:"absensi_challenge",method:"POST",url:"/api/v1/absensi/challenge",desc:"Create a five-minute, single-use challenge before clock-in/out.",headers:"Authorization: ****** application/json",body:`{
  "action": "clock-in",
  "security_metadata": { "platform": "android", "app_version": "1.0.0" }
}`,response:`{
  "data": {
    "action": "clock-in",
    "request_id": "<uuid>",
    "nonce": "<64-character nonce>",
    "expires_at": "<iso8601>"
  }
}`}]},{title:"Cuti (Leave) Module",icon:y,color:"text-purple-600",bg:"bg-purple-50",endpoints:[{id:"cuti_jenis",method:"GET",url:"/api/v1/cuti/jenis",desc:"Get available leave types for dropdown",headers:"Authorization: Bearer {token}",response:`{
  "data": [
    {
      "id": 1,
      "nama_cuti": "Cuti Tahunan",
      "kuota_default": 12,
      "wajib_lampiran": false
    },
    {
      "id": 2,
      "nama_cuti": "Cuti Sakit",
      "kuota_default": 12,
      "wajib_lampiran": true
    }
  ]
}`},{id:"cuti_balances",method:"GET",url:"/api/v1/cuti/balances",desc:"Get leave balances for current year",headers:"Authorization: Bearer {token}",response:`{
  "data": [
    {
      "id": 1,
      "jenis_cuti_id": 1,
      "jenis_cuti": "Cuti Tahunan",
      "saldo_awal": 12,
      "saldo_terpakai": 2,
      "saldo_akhir": 10
    }
  ]
}`},{id:"cuti_requests",method:"GET",url:"/api/v1/cuti/requests",desc:"Get my leave request history",headers:"Authorization: Bearer {token}",response:`{
  "data": [
    {
      "id": 5,
      "jenis_cuti": "Cuti Tahunan",
      "tgl_mulai": "2026-10-01",
      "tgl_selesai": "2026-10-02",
      "jumlah_hari": 2,
      "alasan": "Urusan keluarga",
      "status": "Pending",
      "lampiran": null,
      "created_at": "2026-09-27 08:00:00"
    }
  ]
}`},{id:"cuti_all",method:"GET",url:"/api/v1/cuti/all",desc:"Get all company employee leave requests (HR / Management view).",headers:"Authorization: Bearer {token}",response:`{
  "data": [
    {
      "id": 5,
      "nama_karyawan": "Jane Smith",
      "jenis_cuti": "Cuti Tahunan",
      "status": "Approved"
    }
  ]
}`},{id:"cuti_submit",method:"POST",url:"/api/v1/cuti/request",desc:"Submit a new leave request",headers:`Authorization: Bearer {token}
Content-Type: multipart/form-data`,body:`jenis_cuti_id: 1               (Integer, Required)
tgl_mulai: 2026-10-01          (String YYYY-MM-DD, Required)
tgl_selesai: 2026-10-02        (String YYYY-MM-DD, Required)
keterangan: Sakit (Demam)      (String max 500, Required)
attachment: (File jpg/png/pdf) (Optional – Wajib jika wajib_lampiran: true)`,response:`{
  "message": "Pengajuan cuti berhasil dibuat",
  "data": {
    "id": 5,
    "status": "Pending"
  }
}`},{id:"cuti_cancel",method:"POST",url:"/api/v1/cuti/cancel/{id}",desc:"Cancel the user’s own request once, before its start date.",headers:"Authorization: ****** application/json",body:`{
  "reason": "Perubahan rencana"
}`,response:`{
  "message": "Pengajuan cuti berhasil dibatalkan."
}`}]},{title:"Approval Module (Manager / HR Only)",icon:y,color:"text-amber-600",bg:"bg-amber-50",endpoints:[{id:"cuti_approvals",method:"GET",url:"/api/v1/cuti/approvals",desc:"Get pending approvals (Role: Manajer / HR)",headers:"Authorization: Bearer {token}",response:`{
  "data": [
    {
      "id": 5,
      "nama_karyawan": "Jane Smith",
      "nip": "54321",
      "departemen": "IT",
      "jenis_cuti": "Cuti Tahunan",
      "tgl_mulai": "2026-10-01",
      "tgl_selesai": "2026-10-02",
      "jumlah_hari": 2,
      "alasan": "Urusan keluarga",
      "lampiran": null,
      "status": "Pending",
      "created_at": "2026-09-27 08:00:00"
    }
  ]
}`},{id:"cuti_approve",method:"POST",url:"/api/v1/cuti/approve/{id}",desc:"Approve or Reject a leave request (Role: Manajer / HR)",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "status": "Approved",
  "catatan": "Disetujui, koordinasi dengan tim."
}

// status hanya boleh: "Approved" atau "Rejected"`,response:`{
  "message": "Pengajuan berhasil approved"
}

// Error: { "message": "Pengajuan ini sudah diproses." } (422)
// Error: { "message": "Anda tidak memiliki akses." } (403)`}]},{title:"Dashboard & Unified Approval Center",icon:_,color:"text-amber-600",bg:"bg-amber-50",endpoints:[{id:"dashboard_home",method:"GET",url:"/api/v1/dashboard/home",desc:"BFF dashboard with attendance, leave balance, tasks, approvals, and director overview.",headers:"Authorization: ****** application/json",response:`{
  "data": {
    "user": { },
    "attendance_today": { },
    "leave_balance": { },
    "tasks": { "pending_approvals": 0, "is_approver": false },
    "director_overview": { }
  }
}`},{id:"approval_index",method:"GET",url:"/api/v1/approvals?status=all&per_page=20",desc:"List approval processes assigned to the employee in the token. Default status is Pending.",headers:"Authorization: ****** application/json",response:'{ "data": [], "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 0 } }'},{id:"approval_show",method:"GET",url:"/api/v1/approvals/{approval}",desc:"Show an approval detail and the complete document timeline.",headers:"Authorization: ****** application/json",response:'{ "data": { "approval": { }, "timeline": [] } }'},{id:"approval_action",method:"POST",url:"/api/v1/approvals/{approval}/action",desc:"Approve or reject a Pending step only when the token employee is its target. Membutuhkan verifikasi PIN 6-digit.",headers:"Authorization: ****** application/json",body:`{
  "status": "Approved",
  "pin": "123456",
  "catatan": "Diproses"
}`,response:'{ "message": "Approval berhasil diproses." }'}]},{title:"Task Management, Calendar & Employees",icon:k,color:"text-indigo-600",bg:"bg-indigo-50",endpoints:[{id:"tasks_index",method:"GET",url:"/api/v1/tasks",desc:"Get active tasks assigned to or created by the authenticated user.",headers:"Authorization: ****** application/json",response:'{ "success": true, "data": [] }'},{id:"tasks_store",method:"POST",url:"/api/v1/tasks",desc:"Create a task. Assignment to another employee is restricted to permitted roles.",headers:"Authorization: ****** application/json",body:`{
  "title": "Review laporan",
  "description": "...",
  "priority": "Medium",
  "due_date": "2026-10-10",
  "id_karyawan_assignee": 12
}`,response:'{ "success": true, "message": "Tugas berhasil dibuat", "data": { } }'},{id:"tasks_history",method:"GET",url:"/api/v1/tasks/history",desc:"Get paginated task history including archived tasks.",headers:"Authorization: ****** application/json",response:'{ "success": true, "data": { "data": [], "meta": { } } }'},{id:"tasks_status",method:"POST",url:"/api/v1/tasks/{id}/status",desc:"Change task status or archive a task.",headers:"Authorization: ****** application/json",body:'{ "status": "In Progress", "archive": false }',response:'{ "success": true, "message": "Status tugas berhasil diperbarui", "data": { } }'},{id:"calendar_index",method:"GET",url:"/api/v1/calendar?month=10&year=2026",desc:"Get company calendar events, holidays, leaves, and tasks for mobile.",headers:"Authorization: ****** application/json",response:'{ "status": "success", "data": [] }'},{id:"calendar_store",method:"POST",url:"/api/v1/calendar/events",desc:"Create a new company agenda or book a meeting room (Role HR: Super Admin, HR Manager, HR Staff). Otomatis memvalidasi pencegahan bentrok ruangan.",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "title": "Rapat Koordinasi Manajemen",
  "category": "meeting",
  "start_date": "2026-10-25 09:00:00",
  "end_date": "2026-10-25 11:00:00",
  "location": "Ruang Meeting Utama",
  "description": "Pembahasan target"
}

// category: meeting, general, training, online, other`,response:`{
  "status": "success",
  "message": "Agenda berhasil ditambahkan ke kalender.",
  "data": { "id": 15, "title": "Rapat Koordinasi Manajemen" }
}

// Error Bentrok: 422 Unprocessable Content
// {"errors": {"location": ["Ruangan 'Ruang Meeting Utama' sudah dibooking oleh agenda lain pada jam tersebut."]}}`},{id:"employees_index",method:"GET",url:"/api/v1/karyawan",desc:"Get employees available to the authenticated mobile user.",headers:"Authorization: ****** application/json",response:'{ "data": [] }'}]},{title:"PIN Security & Keamanan Transaksi",icon:P,color:"text-rose-600",bg:"bg-rose-50",endpoints:[{id:"pin_status",method:"GET",url:"/api/v1/pin/status",desc:"Check if authenticated user has set their 6-digit security PIN.",headers:"Authorization: Bearer {token}",response:`{
  "has_pin": true
}`},{id:"pin_set",method:"POST",url:"/api/v1/pin/set",desc:"Set 6-digit security PIN for the first time.",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "pin": "123456",
  "pin_confirmation": "123456"
}`,response:`{
  "message": "PIN berhasil dibuat."
}`},{id:"pin_change",method:"POST",url:"/api/v1/pin/change",desc:"Change existing 6-digit security PIN with current PIN verification.",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "current_pin": "123456",
  "pin": "654321",
  "pin_confirmation": "654321"
}`,response:`{
  "message": "PIN berhasil diubah."
}`},{id:"pin_verify",method:"POST",url:"/api/v1/pin/verify",desc:"Verify 6-digit PIN before executing critical approval actions.",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "pin": "123456"
}`,response:`{
  "valid": true
}

// Error 422: {"errors": {"pin": ["PIN yang Anda masukkan salah."]}}`}]},{title:"Push Notifications & Real-Time Sync",icon:M,color:"text-red-600",bg:"bg-red-50",endpoints:[{id:"notif_index",method:"GET",url:"/api/v1/notifications?limit=30&filter=unread",desc:"Get in-app notifications with unread counter.",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "unread_count": 2,
  "data": [
    {
      "id": "uuid-123",
      "type": "approval",
      "title": "Approval Dibutuhkan",
      "message": "Pengajuan PJ-2026-004 menunggu persetujuan Anda.",
      "link": "/approvals",
      "is_read": false,
      "created_at": "2026-10-21T08:00:00Z"
    }
  ]
}`},{id:"notif_mark_read",method:"POST",url:"/api/v1/notifications/{id}/read",desc:"Mark single notification as read.",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "message": "Notifikasi ditandai telah dibaca.",
  "unread_count": 1
}`},{id:"notif_mark_all",method:"POST",url:"/api/v1/notifications/read-all",desc:"Mark all unread notifications as read.",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "message": "Semua notifikasi berhasil ditandai sudah dibaca.",
  "unread_count": 0
}`},{id:"notif_get_prefs",method:"GET",url:"/api/v1/notifications/preferences",desc:"Get user notification category preferences.",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "data": {
    "approval_notifications": true,
    "leave_notifications": true,
    "attendance_notifications": true,
    "task_notifications": true,
    "announcement_notifications": true
  }
}`},{id:"notif_update_prefs",method:"POST",url:"/api/v1/notifications/preferences",desc:"Update user notification category preferences.",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "approval_notifications": true,
  "attendance_notifications": false
}`,response:`{
  "status": "success",
  "message": "Pengaturan notifikasi berhasil diperbarui."
}`},{id:"notif_device_token",method:"POST",url:"/api/v1/notifications/device-token",desc:"Register or refresh mobile push device token (Firebase Cloud Messaging / APNS).",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "token": "fcm-device-token-string",
  "platform": "android",
  "device_name": "Samsung Galaxy S24"
}`,response:`{
  "status": "success",
  "message": "Device token berhasil didaftarkan."
}`}]},{title:"Employee Self-Service (ESS) Module",icon:S,color:"text-teal-600",bg:"bg-teal-50",endpoints:[{id:"ess_correction_get",method:"GET",url:"/api/v1/ess/attendance-correction",desc:"Get paginated history of attendance corrections submitted by employee.",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "data": [],
  "meta": { "current_page": 1, "total": 0 }
}`},{id:"ess_correction_post",method:"POST",url:"/api/v1/ess/attendance-correction",desc:"Submit attendance time correction request to supervisor.",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "tanggal": "2026-10-20",
  "waktu_masuk_baru": "08:30",
  "waktu_keluar_baru": "17:30",
  "alasan": "Fingerprint error di kantor pusat"
}`,response:`{
  "status": "success",
  "message": "Pengajuan koreksi absensi berhasil dikirim."
}`},{id:"ess_trip_get",method:"GET",url:"/api/v1/ess/business-trip",desc:"Get paginated history of business trips / external duty letters.",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "data": [],
  "meta": { "current_page": 1, "total": 0 }
}`},{id:"ess_trip_post",method:"POST",url:"/api/v1/ess/business-trip",desc:"Submit new business trip request with automated duty letter number.",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "nama_kegiatan": "Liputan Pertandingan Tandang",
  "lokasi_tujuan": "Bandung",
  "tgl_mulai": "2026-10-22",
  "tgl_selesai": "2026-10-23",
  "keterangan": "Tim Media Official"
}`,response:`{
  "status": "success",
  "message": "Pengajuan dinas luar berhasil didaftarkan."
}`},{id:"ess_payslips",method:"GET",url:"/api/v1/ess/payslips",desc:"Get list of published salary payslips for employee.",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "data": [
    {
      "id": 1,
      "periode": "September 2026",
      "gaji_pokok": 8000000,
      "gaji_bersih": 9200000
    }
  ]
}`},{id:"ess_payslip_detail",method:"GET",url:"/api/v1/ess/payslips/{id}",desc:"Get full payslip breakdown (salary, allowances, deductions, net salary).",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "data": {
    "periode": "September 2026",
    "ringkasan": { "gaji_pokok": 8000000, "gaji_bersih": 9200000 },
    "rincian_komponen": []
  }
}`},{id:"ess_reimbursement_get",method:"GET",url:"/api/v1/ess/reimbursements",desc:"Get paginated employee reimbursement claims.",headers:"Authorization: Bearer {token}",response:`{
  "status": "success",
  "data": [],
  "meta": { "current_page": 1, "total": 0 }
}`},{id:"ess_reimbursement_post",method:"POST",url:"/api/v1/ess/reimbursements",desc:"Submit employee expense reimbursement claim.",headers:`Authorization: Bearer {token}
Content-Type: application/json`,body:`{
  "judul_klaim": "Bensin Liputan",
  "kategori": "Transport",
  "tgl_transaksi": "2026-10-20",
  "total_biaya": 150000,
  "keterangan": "Operasional liputan tim"
}`,response:`{
  "status": "success",
  "message": "Pengajuan reimbursement berhasil didaftarkan."
}`}]}],T=d=>{switch(d){case"GET":return"bg-emerald-100 text-emerald-700 border-emerald-200";case"POST":return"bg-blue-100 text-blue-700 border-blue-200";case"PUT":case"PATCH":return"bg-amber-100 text-amber-700 border-amber-200";case"DELETE":return"bg-red-100 text-red-700 border-red-200";default:return"bg-gray-100 text-gray-700 border-gray-200"}};return(d,a)=>(t(),s(m,null,[c(u(z),{title:"API Documentation"}),c(G,null,{header:v(()=>[e("div",I,[c(u(k),{class:"w-6 h-6 text-gray-600"}),a[0]||(a[0]=e("h2",{class:"font-semibold text-xl text-gray-800 leading-tight"},"Mobile API Documentation",-1))])]),default:v(()=>[e("div",N,[e("div",q,[e("div",H,[e("div",F,[e("div",L,[e("div",U,[c(u(_),{class:"w-6 h-6 text-indigo-600"})]),a[1]||(a[1]=e("div",null,[e("h3",{class:"text-lg font-bold text-gray-900"},"Developer Guide (Phase 1)"),e("p",{class:"text-gray-600 mt-1"},"This API is designed specifically for Native Android & iOS applications to enforce hardware-based GPS locations."),e("div",{class:"mt-4 grid grid-cols-1 md:grid-cols-3 gap-4"},[e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Base Configurations"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium"},"Base URL:"),i(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"https://your-domain.com/api/v1")]),e("li",null,[e("span",{class:"font-medium"},"Auth:"),i(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"Bearer {token}")]),e("li",null,[e("span",{class:"font-medium"},"Accept:"),i(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"application/json")])])]),e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Success Response (JSend)"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium text-emerald-600"},"200 OK:"),i(),e("code",null,'{"data": {...}}')]),e("li",null,[e("span",{class:"font-medium text-emerald-600"},"201 Created:"),i(),e("code",null,'{"message":"..","data":{}}')])])]),e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Error Responses"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium text-amber-600"},"401:"),i(" Token expired → redirect Login")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"403:"),i(" Akses ditolak (role)")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"404:"),i(" Data tidak ditemukan")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"422:"),i(),e("code",null,'{"errors":{"field":[...]}}')])])])])],-1))])])]),e("div",$,[(t(),s(m,null,f(w,(l,A)=>e("div",{key:A,class:"bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"},[e("div",{class:h(["p-4 border-b border-gray-100 flex items-center gap-3",l.bg])},[(t(),g(C(l.icon),{class:h(["w-5 h-5",l.color])},null,8,["class"])),e("h3",J,r(l.title),1)],2),e("div",Y,[e("ul",V,[(t(!0),s(m,null,f(l.endpoints,(n,j)=>(t(),s("li",{key:j,class:"p-0 hover:bg-gray-50 transition-colors"},[e("div",{onClick:oe=>x(n.id),class:"p-4 cursor-pointer flex justify-between items-center w-full"},[e("div",null,[e("div",W,[e("span",{class:h(["px-2.5 py-1 text-xs font-bold rounded border shrink-0 w-max",T(n.method)])},r(n.method),3),e("code",Q,r(n.url),1)]),e("p",Z,r(n.desc),1)]),o.value!==n.id?(t(),g(u(B),{key:0,class:"w-5 h-5 text-gray-400"})):(t(),g(u(D),{key:1,class:"w-5 h-5 text-gray-400"}))],8,K),o.value===n.id?(t(),s("div",X,[e("div",ee,[e("div",null,[a[4]||(a[4]=e("h4",{class:"font-bold text-gray-700 mb-2"},"Request",-1)),n.headers?(t(),s("div",ae,[a[2]||(a[2]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Headers",-1)),e("pre",ne,[e("code",null,r(n.headers),1)])])):p("",!0),n.body?(t(),s("div",te,[a[3]||(a[3]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Payload / Body",-1)),e("pre",se,[e("code",null,r(n.body),1)])])):p("",!0),!n.body&&!n.headers?(t(),s("div",ie,"No additional payload required.")):p("",!0)]),e("div",null,[a[5]||(a[5]=e("h4",{class:"font-bold text-gray-700 mb-2"},"Success Response",-1)),a[6]||(a[6]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Format (JSON)",-1)),e("pre",re,[e("code",null,r(n.response),1)])])])])):p("",!0)]))),128))])])])),64))])])])]),_:1})],64))}};export{Te as default};
