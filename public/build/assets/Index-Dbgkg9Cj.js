import{c as b,d as _,e as v,f as P,A as S}from"./AuthenticatedLayout-Cd6pWwL3.js";import{c as o,d as e,m as n,b as u,u as c,h as C,w as y,e as s,F as m,x as k,g as h,s as g,C as G,t as r,f as p,r as z}from"./app-CVmPOJxa.js";import{r as E}from"./KeyIcon-BOtUSzvn.js";import{r as B}from"./sweetalert2.esm.all-DIjIVPB2.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";import"./CheckCircleIcon-CLaWP9Mo.js";import"./XCircleIcon-HNtnUeLp.js";import"./Modal-Ccl5Ka9N.js";import"./XMarkIcon-DjWDg7jK.js";import"./SecondaryButton-GgG8TAbw.js";import"./DangerButton-CDSt-Jen.js";import"./CalendarIcon-Cb2ZtyYl.js";function O(x,i){return n(),o("svg",{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24","stroke-width":"1.5",stroke:"currentColor","aria-hidden":"true","data-slot":"icon"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"m4.5 15.75 7.5-7.5 7.5 7.5"})])}const R={class:"flex items-center gap-3"},D={class:"py-12"},q={class:"max-w-7xl mx-auto sm:px-6 lg:px-8"},M={class:"bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"},I={class:"p-6 text-gray-900 border-b border-gray-200"},F={class:"flex items-start gap-4"},H={class:"p-3 bg-indigo-50 rounded-lg shrink-0"},L={class:"grid grid-cols-1 gap-6"},Y={class:"font-bold text-gray-900"},N={class:"p-0"},$={class:"divide-y divide-gray-100"},J=["onClick"],U={class:"flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-2"},V={class:"text-sm font-semibold text-gray-800 break-all"},W={class:"text-sm text-gray-600 sm:ml-[72px]"},K={key:0,class:"p-4 bg-gray-50 border-t border-gray-200 text-sm"},Q={class:"grid grid-cols-1 md:grid-cols-2 gap-6"},X={key:0,class:"mb-3"},Z={class:"mt-1 bg-gray-800 text-green-400 p-3 rounded-md overflow-x-auto"},ee={key:1},ae={class:"mt-1 bg-gray-800 text-blue-300 p-3 rounded-md overflow-x-auto"},te={key:2,class:"text-gray-500 italic"},ne={class:"mt-1 bg-gray-800 text-emerald-400 p-3 rounded-md overflow-x-auto"},_e={__name:"Index",setup(x){const i=z(null),f=d=>{i.value=i.value===d?null:d},w=[{title:"Authentication Module",icon:E,color:"text-blue-600",bg:"bg-blue-50",endpoints:[{id:"login",method:"POST",url:"/api/v1/login",desc:"Login to get Sanctum token",body:`{
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
}`}]},{title:"Absensi (Attendance) Module",icon:B,color:"text-green-600",bg:"bg-green-50",endpoints:[{id:"absensi_today",method:"GET",url:"/api/v1/absensi/today",desc:"Check today's attendance status",headers:"Authorization: Bearer {token}",response:`{
  "data": {
    "clock_in": "08:00:00",
    "clock_out": null,
    "status": "Hadir"
  }
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
}`}]},{title:"Cuti (Leave) Module",icon:_,color:"text-purple-600",bg:"bg-purple-50",endpoints:[{id:"cuti_jenis",method:"GET",url:"/api/v1/cuti/jenis",desc:"Get available leave types for dropdown",headers:"Authorization: Bearer {token}",response:`{
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
}`}]},{title:"Approval Module (Manager / HR Only)",icon:_,color:"text-amber-600",bg:"bg-amber-50",endpoints:[{id:"cuti_approvals",method:"GET",url:"/api/v1/cuti/approvals",desc:"Get pending approvals (Role: Manajer / HR)",headers:"Authorization: Bearer {token}",response:`{
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
// Error: { "message": "Anda tidak memiliki akses." } (403)`}]},{title:"Dashboard & Unified Approval Center",icon:b,color:"text-amber-600",bg:"bg-amber-50",endpoints:[{id:"dashboard_home",method:"GET",url:"/api/v1/dashboard/home",desc:"BFF dashboard with attendance, leave balance, tasks, approvals, and director overview.",headers:"Authorization: ****** application/json",response:`{
  "data": {
    "user": { },
    "attendance_today": { },
    "leave_balance": { },
    "tasks": { "pending_approvals": 0, "is_approver": false },
    "director_overview": { }
  }
}`},{id:"approval_index",method:"GET",url:"/api/v1/approvals?status=all&per_page=20",desc:"List approval processes assigned to the employee in the token. Default status is Pending.",headers:"Authorization: ****** application/json",response:'{ "data": [], "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 0 } }'},{id:"approval_show",method:"GET",url:"/api/v1/approvals/{approval}",desc:"Show an approval detail and the complete document timeline.",headers:"Authorization: ****** application/json",response:'{ "data": { "approval": { }, "timeline": [] } }'},{id:"approval_action",method:"POST",url:"/api/v1/approvals/{approval}/action",desc:"Approve or reject a Pending step only when the token employee is its target.",headers:"Authorization: ****** application/json",body:`{
  "status": "Approved",
  "pin": "123456",
  "catatan": "Diproses"
}`,response:'{ "message": "Approval berhasil diproses." }'}]},{title:"Task Management, Calendar & Employees",icon:v,color:"text-indigo-600",bg:"bg-indigo-50",endpoints:[{id:"tasks_index",method:"GET",url:"/api/v1/tasks",desc:"Get active tasks assigned to or created by the authenticated user.",headers:"Authorization: ****** application/json",response:'{ "success": true, "data": [] }'},{id:"tasks_store",method:"POST",url:"/api/v1/tasks",desc:"Create a task. Assignment to another employee is restricted to permitted roles.",headers:"Authorization: ****** application/json",body:`{
  "title": "Review laporan",
  "description": "...",
  "priority": "Medium",
  "due_date": "2026-10-10",
  "id_karyawan_assignee": 12
}`,response:'{ "success": true, "message": "Tugas berhasil dibuat", "data": { } }'},{id:"tasks_history",method:"GET",url:"/api/v1/tasks/history",desc:"Get paginated task history including archived tasks.",headers:"Authorization: ****** application/json",response:'{ "success": true, "data": { "data": [], "meta": { } } }'},{id:"tasks_status",method:"POST",url:"/api/v1/tasks/{id}/status",desc:"Change task status or archive a task.",headers:"Authorization: ****** application/json",body:'{ "status": "In Progress", "archive": false }',response:'{ "success": true, "message": "Status tugas berhasil diperbarui", "data": { } }'},{id:"calendar_index",method:"GET",url:"/api/v1/calendar",desc:"Get company calendar events for mobile.",headers:"Authorization: ****** application/json",response:'{ "data": [] }'},{id:"employees_index",method:"GET",url:"/api/v1/karyawan",desc:"Get employees available to the authenticated mobile user.",headers:"Authorization: ****** application/json",response:'{ "data": [] }'}]}],j=d=>{switch(d){case"GET":return"bg-emerald-100 text-emerald-700 border-emerald-200";case"POST":return"bg-blue-100 text-blue-700 border-blue-200";case"PUT":case"PATCH":return"bg-amber-100 text-amber-700 border-amber-200";case"DELETE":return"bg-red-100 text-red-700 border-red-200";default:return"bg-gray-100 text-gray-700 border-gray-200"}};return(d,a)=>(n(),o(m,null,[u(c(C),{title:"API Documentation"}),u(S,null,{header:y(()=>[e("div",R,[u(c(v),{class:"w-6 h-6 text-gray-600"}),a[0]||(a[0]=e("h2",{class:"font-semibold text-xl text-gray-800 leading-tight"},"Mobile API Documentation",-1))])]),default:y(()=>[e("div",D,[e("div",q,[e("div",M,[e("div",I,[e("div",F,[e("div",H,[u(c(b),{class:"w-6 h-6 text-indigo-600"})]),a[1]||(a[1]=e("div",null,[e("h3",{class:"text-lg font-bold text-gray-900"},"Developer Guide (Phase 1)"),e("p",{class:"text-gray-600 mt-1"},"This API is designed specifically for Native Android & iOS applications to enforce hardware-based GPS locations."),e("div",{class:"mt-4 grid grid-cols-1 md:grid-cols-3 gap-4"},[e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Base Configurations"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium"},"Base URL:"),s(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"https://your-domain.com/api/v1")]),e("li",null,[e("span",{class:"font-medium"},"Auth:"),s(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"Bearer {token}")]),e("li",null,[e("span",{class:"font-medium"},"Accept:"),s(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"application/json")])])]),e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Success Response (JSend)"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium text-emerald-600"},"200 OK:"),s(),e("code",null,'{"data": {...}}')]),e("li",null,[e("span",{class:"font-medium text-emerald-600"},"201 Created:"),s(),e("code",null,'{"message":"..","data":{}}')])])]),e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Error Responses"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium text-amber-600"},"401:"),s(" Token expired → redirect Login")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"403:"),s(" Akses ditolak (role)")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"404:"),s(" Data tidak ditemukan")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"422:"),s(),e("code",null,'{"errors":{"field":[...]}}')])])])])],-1))])])]),e("div",L,[(n(),o(m,null,k(w,(l,A)=>e("div",{key:A,class:"bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"},[e("div",{class:h(["p-4 border-b border-gray-100 flex items-center gap-3",l.bg])},[(n(),g(G(l.icon),{class:h(["w-5 h-5",l.color])},null,8,["class"])),e("h3",Y,r(l.title),1)],2),e("div",N,[e("ul",$,[(n(!0),o(m,null,k(l.endpoints,(t,T)=>(n(),o("li",{key:T,class:"p-0 hover:bg-gray-50 transition-colors"},[e("div",{onClick:se=>f(t.id),class:"p-4 cursor-pointer flex justify-between items-center w-full"},[e("div",null,[e("div",U,[e("span",{class:h(["px-2.5 py-1 text-xs font-bold rounded border shrink-0 w-max",j(t.method)])},r(t.method),3),e("code",V,r(t.url),1)]),e("p",W,r(t.desc),1)]),i.value!==t.id?(n(),g(c(P),{key:0,class:"w-5 h-5 text-gray-400"})):(n(),g(c(O),{key:1,class:"w-5 h-5 text-gray-400"}))],8,J),i.value===t.id?(n(),o("div",K,[e("div",Q,[e("div",null,[a[4]||(a[4]=e("h4",{class:"font-bold text-gray-700 mb-2"},"Request",-1)),t.headers?(n(),o("div",X,[a[2]||(a[2]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Headers",-1)),e("pre",Z,[e("code",null,r(t.headers),1)])])):p("",!0),t.body?(n(),o("div",ee,[a[3]||(a[3]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Payload / Body",-1)),e("pre",ae,[e("code",null,r(t.body),1)])])):p("",!0),!t.body&&!t.headers?(n(),o("div",te,"No additional payload required.")):p("",!0)]),e("div",null,[a[5]||(a[5]=e("h4",{class:"font-bold text-gray-700 mb-2"},"Success Response",-1)),a[6]||(a[6]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Format (JSON)",-1)),e("pre",ne,[e("code",null,r(t.response),1)])])])])):p("",!0)]))),128))])])])),64))])])])]),_:1})],64))}};export{_e as default};
