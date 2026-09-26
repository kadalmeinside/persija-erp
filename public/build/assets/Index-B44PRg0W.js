import{c as T,d as y,e as A,f as S,A as C}from"./AuthenticatedLayout-Bhz5gsi8.js";import{c as s,d as e,m as n,b as c,u,h as P,w as x,e as o,F as m,x as _,g,s as h,C as B,t as r,f as p,r as G}from"./app-D3tZhZ6h.js";import{r as R}from"./sweetalert2.esm.all-cB4dAy5j.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";import"./CheckCircleIcon-CkqtD9Wm.js";import"./XCircleIcon-BqWUb3oc.js";import"./Modal-DL_jVNS-.js";import"./XMarkIcon-CF2zKdFE.js";import"./SecondaryButton-Z1jOoX1a.js";import"./DangerButton-Dg2TxnJQ.js";import"./CalendarIcon-DCbGS5wj.js";function E(b,i){return n(),s("svg",{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24","stroke-width":"1.5",stroke:"currentColor","aria-hidden":"true","data-slot":"icon"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"m4.5 15.75 7.5-7.5 7.5 7.5"})])}function D(b,i){return n(),s("svg",{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24","stroke-width":"1.5",stroke:"currentColor","aria-hidden":"true","data-slot":"icon"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"})])}const M={class:"flex items-center gap-3"},q={class:"py-12"},z={class:"max-w-7xl mx-auto sm:px-6 lg:px-8"},O={class:"bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"},H={class:"p-6 text-gray-900 border-b border-gray-200"},I={class:"flex items-start gap-4"},L={class:"p-3 bg-indigo-50 rounded-lg shrink-0"},Y={class:"grid grid-cols-1 gap-6"},N={class:"font-bold text-gray-900"},$={class:"p-0"},F={class:"divide-y divide-gray-100"},J=["onClick"],U={class:"flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-2"},V={class:"text-sm font-semibold text-gray-800 break-all"},K={class:"text-sm text-gray-600 sm:ml-[72px]"},Q={key:0,class:"p-4 bg-gray-50 border-t border-gray-200 text-sm"},W={class:"grid grid-cols-1 md:grid-cols-2 gap-6"},Z={key:0,class:"mb-3"},X={class:"mt-1 bg-gray-800 text-green-400 p-3 rounded-md overflow-x-auto"},ee={key:1},te={class:"mt-1 bg-gray-800 text-blue-300 p-3 rounded-md overflow-x-auto"},ae={key:2,class:"text-gray-500 italic"},ne={class:"mt-1 bg-gray-800 text-emerald-400 p-3 rounded-md overflow-x-auto"},be={__name:"Index",setup(b){const i=G(null),v=d=>{i.value=i.value===d?null:d},k=[{title:"Authentication Module",icon:D,color:"text-blue-600",bg:"bg-blue-50",endpoints:[{id:"login",method:"POST",url:"/api/v1/login",desc:"Login to get Sanctum token",body:`{
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
}`}]},{title:"Absensi (Attendance) Module",icon:R,color:"text-green-600",bg:"bg-green-50",endpoints:[{id:"absensi_today",method:"GET",url:"/api/v1/absensi/today",desc:"Check today's attendance status",headers:"Authorization: Bearer {token}",response:`{
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
// Error: { "message": "Anda tidak memiliki akses." } (403)`}]}],f=d=>{switch(d){case"GET":return"bg-emerald-100 text-emerald-700 border-emerald-200";case"POST":return"bg-blue-100 text-blue-700 border-blue-200";case"PUT":case"PATCH":return"bg-amber-100 text-amber-700 border-amber-200";case"DELETE":return"bg-red-100 text-red-700 border-red-200";default:return"bg-gray-100 text-gray-700 border-gray-200"}};return(d,t)=>(n(),s(m,null,[c(u(P),{title:"API Documentation"}),c(C,null,{header:x(()=>[e("div",M,[c(u(S),{class:"w-6 h-6 text-gray-600"}),t[0]||(t[0]=e("h2",{class:"font-semibold text-xl text-gray-800 leading-tight"},"Mobile API Documentation",-1))])]),default:x(()=>[e("div",q,[e("div",z,[e("div",O,[e("div",H,[e("div",I,[e("div",L,[c(u(T),{class:"w-6 h-6 text-indigo-600"})]),t[1]||(t[1]=e("div",null,[e("h3",{class:"text-lg font-bold text-gray-900"},"Developer Guide (Phase 1)"),e("p",{class:"text-gray-600 mt-1"},"This API is designed specifically for Native Android & iOS applications to enforce hardware-based GPS locations."),e("div",{class:"mt-4 grid grid-cols-1 md:grid-cols-3 gap-4"},[e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Base Configurations"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium"},"Base URL:"),o(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"https://your-domain.com/api/v1")]),e("li",null,[e("span",{class:"font-medium"},"Auth:"),o(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"Bearer {token}")]),e("li",null,[e("span",{class:"font-medium"},"Accept:"),o(),e("code",{class:"bg-gray-200 px-1 py-0.5 rounded text-indigo-700"},"application/json")])])]),e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Success Response (JSend)"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium text-emerald-600"},"200 OK:"),o(),e("code",null,'{"data": {...}}')]),e("li",null,[e("span",{class:"font-medium text-emerald-600"},"201 Created:"),o(),e("code",null,'{"message":"..","data":{}}')])])]),e("div",{class:"bg-gray-50 p-4 rounded-lg border border-gray-200"},[e("h4",{class:"font-semibold text-gray-700 mb-2"},"Error Responses"),e("ul",{class:"space-y-1 text-sm text-gray-600"},[e("li",null,[e("span",{class:"font-medium text-amber-600"},"401:"),o(" Token expired → redirect Login")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"403:"),o(" Akses ditolak (role)")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"404:"),o(" Data tidak ditemukan")]),e("li",null,[e("span",{class:"font-medium text-red-600"},"422:"),o(),e("code",null,'{"errors":{"field":[...]}}')])])])])],-1))])])]),e("div",Y,[(n(),s(m,null,_(k,(l,w)=>e("div",{key:w,class:"bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"},[e("div",{class:g(["p-4 border-b border-gray-100 flex items-center gap-3",l.bg])},[(n(),h(B(l.icon),{class:g(["w-5 h-5",l.color])},null,8,["class"])),e("h3",N,r(l.title),1)],2),e("div",$,[e("ul",F,[(n(!0),s(m,null,_(l.endpoints,(a,j)=>(n(),s("li",{key:j,class:"p-0 hover:bg-gray-50 transition-colors"},[e("div",{onClick:se=>v(a.id),class:"p-4 cursor-pointer flex justify-between items-center w-full"},[e("div",null,[e("div",U,[e("span",{class:g(["px-2.5 py-1 text-xs font-bold rounded border shrink-0 w-max",f(a.method)])},r(a.method),3),e("code",V,r(a.url),1)]),e("p",K,r(a.desc),1)]),i.value!==a.id?(n(),h(u(A),{key:0,class:"w-5 h-5 text-gray-400"})):(n(),h(u(E),{key:1,class:"w-5 h-5 text-gray-400"}))],8,J),i.value===a.id?(n(),s("div",Q,[e("div",W,[e("div",null,[t[4]||(t[4]=e("h4",{class:"font-bold text-gray-700 mb-2"},"Request",-1)),a.headers?(n(),s("div",Z,[t[2]||(t[2]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Headers",-1)),e("pre",X,[e("code",null,r(a.headers),1)])])):p("",!0),a.body?(n(),s("div",ee,[t[3]||(t[3]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Payload / Body",-1)),e("pre",te,[e("code",null,r(a.body),1)])])):p("",!0),!a.body&&!a.headers?(n(),s("div",ae,"No additional payload required.")):p("",!0)]),e("div",null,[t[5]||(t[5]=e("h4",{class:"font-bold text-gray-700 mb-2"},"Success Response",-1)),t[6]||(t[6]=e("span",{class:"text-xs font-semibold text-gray-500 uppercase tracking-wider"},"Format (JSON)",-1)),e("pre",ne,[e("code",null,r(a.response),1)])])])])):p("",!0)]))),128))])])])),64))])])])]),_:1})],64))}};export{be as default};
