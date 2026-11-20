# GEMINI.md - Standar Kepatuhan Pengembangan, UI/UX & Keamanan Persija ERP

Dokumen ini adalah aturan wajib (*project compliance instruction*) bagi setiap AI Coding Agent maupun developer yang membaca, merencanakan, atau mengubah kode di repository **Persija ERP** ([`persija-erp`](file:///Users/macbook/Application/persija-erp)).

```text
antislop active: during (project rule).
Reading this as: Enterprise HR, Finance & Operations ERP Web Application for internal staff, managers, HR, finance, and directors, in a structured high-contrast enterprise visual language, dial ENERGY 1 / RHYTHM 2 / MOTION 1.
```

---

## 1. Aturan Mutlak Ikon & Larangan Emoticon / Emoji (`R-04`)

1. **DILARANG KERAS menggunakan emoticon atau karakter emoji Unicode** di seluruh bagian proyek, termasuk:
   - Template Vue (`resources/js/**/*.vue`), Blade (`resources/views/**/*.blade.php`), dan file JavaScript/CSS.
   - Pesan flash, toast, error validasi, judul notifikasi, log pesan user-facing, dan seeder di PHP (`app/**/*.php`, `database/**/*.php`).
   - Dokumen teknis, rencana kerja, dan tracker proyek.
2. **Hanya Gunakan Ikon SVG Semantik**:
   - Gunakan ikon dari `@heroicons/vue/24/outline` (atau `24/solid` untuk state aktif) yang relevan langsung dengan makna konten ERP (misalnya `ExclamationTriangleIcon` untuk peringatan, `InformationCircleIcon` untuk informasi, `LockClosedIcon` untuk kunci periode/keamanan, `BanknotesIcon` untuk pembayaran, `MapPinIcon` untuk lokasi).
   - Dilarang menggunakan ikon klise AI (sparkle, magic wand, robot, orb, rocket, lightning dekoratif) tanpa fungsi domain ERP yang nyata (`R-04`).
   - Semua ikon dekoratif yang mendampingi teks wajib diberi atribut `aria-hidden="true"`.
   - Jika sebuah kontrol hanya berisi ikon tanpa teks (*icon-only button*), wajib berupa elemen `<button type="button">` dengan `aria-label="..."` yang jelas (`R-39`).

---

## 2. Aturan Kepatuhan UI/UX (`/antislop-ui`, `antislop`, & `/antislop-human`)

Setiap pembuatan atau penyuntingan antarmuka Web (Vue 3 + Inertia + Tailwind CSS) wajib mematuhi seluruh standar berikut:

### A. Warna, Kontras & Mode Terang/Gelap (`R-01`, `R-21`, `R-25`, `R-29`, `R-34`)
- **Batas Palet Aktif (`R-29`)**: Maksimal 2 hingga 3 warna inti (Neutral Slate/Gray sebagai kanvas + Persija Crimson/Maroon sebagai warna identitas primer) dan 1 warna aksen fokus per konteks, ditambah token semantik terbatas untuk status ERP:
  - Disetujui / Lunas / Aktif: Emerald
  - Menunggu / Revisi / Peringatan: Amber
  - Ditolak / Terlambat / Error: Red
- **Larangan Gradien & Warna Acak (`R-01`)**: Dilarang menggunakan gradien biru-ke-ungu, biru-ke-cyan, ungu-ke-pink, glow berwarna di seluruh kartu, atau memberi warna berbeda pada setiap kartu di halaman yang sama tanpa makna hierarki.
- **Kontras WCAG AA (`R-25`)**: Setiap pasangan teks dan latar belakang wajib memenuhi rasio minimal `4.5:1` untuk teks normal dan `3:1` untuk teks besar (`>= 18px`) serta batas komponen interaktif (`border` input/tombol/segmen grafik). Dilarang memakai teks abu-abu terang di atas latar abu-abu muda/putih.
- **Keseimbangan Light & Dark Mode (`R-21`, `R-34`)**: Setiap komponen baru atau hasil refactor harus teruji utuh pada **Light Mode dan Dark Mode** tanpa kontras rusak atau teks tak terbaca.

### B. Layout, Dekorasi, Bayangan & Radius (`R-03`, `R-05`, `R-07`, `R-10`, `R-11`, `R-12`, `R-13`)
- **Konsistensi Padding Layout**: Patuhi [`.agents/rules/mobile-padding.md`](file:///Users/macbook/Application/persija-erp/.agents/rules/mobile-padding.md). Jangan menambahkan `px-4 sm:px-6 lg:px-8` pada pembungkus terluar di dalam `<AuthenticatedLayout>` karena layout utama sudah menerapkan `px-2 py-4 sm:p-6`.
- **Responsivitas Mobile (`R-03`)**: Dilarang ada *horizontal overflow* halaman, teks meluber keluar kartu, atau target sentuh di bawah `44px` pada breakpoint mobile (`375px`).
- **Bebas Dekorasi Slop (`R-07`, `R-10`, `R-12`, `R-13`)**:
  - Dilarang memakai *background grid*, *blueprint lines*, atau *dot matrix* sebagai hiasan latar.
  - Dilarang memakai *colored left stripe* pada kartu/baris jika tidak menandai state fungsional nyata.
  - `backdrop-blur` (glassmorphism) dibatasi maksimal 1 elemen overlay (mis. backdrop modal), bukan di navbar + sidebar + kartu secara bersamaan.
  - Gunakan `border` tipis dan `shadow-sm` untuk kartu biasa; `shadow-lg`/`shadow-xl` hanya untuk modal atau dropdown yang melayang di atas kanvas.
- **Skala Border Radius (`R-11`)**: Tetapkan hierarki radius yang konsisten (`rounded-lg` untuk tombol/input, `rounded-xl` untuk kartu/panel/modal, `rounded-md` untuk badge status). Dilarang menjadikan seluruh elemen berbentuk kapsul (`rounded-full`).
- **Decision-Driven Workspace (`C-3`, `R-05`)**: Jangan menduplikasi template "4 kartu stat + chart acak + feed dummy". Susun hierarki halaman berdasarkan keputusan utama yang harus diambil pengguna pada halaman tersebut.

### C. Tipografi, Angka Akuntansi & Copywriting (`R-02`, `R-06`, `R-15`, `R-16`, `R-17`, `R-23`)
- **Larangan Em-Dash (`R-02`)**: Dilarang menggunakan karakter em-dash (`—`) pada teks UI, label, maupun pesan. Gunakan koma (`,`), titik dua (`:`), atau tanda kurung `()`.
- **Angka Terukur & Sejajar (`R-06`, `R-17`)**: Seluruh nominal mata uang (`Rp`), saldo cuti, persentase, NIK, kode akun, dan tanggal di tabel/kartu wajib menggunakan class `tabular-nums`. Dilarang menampilkan angka statistik fiktif atau delta tanpa data backend nyata.
- **Copywriting & Placeholder (`R-15`, `R-16`, `R-23`, `R-38`)**: Gunakan label tombol spesifik terhadap aksi ERP (contoh: `"Ajukan Cuti"`, `"Setujui Pengajuan"`, `"Catat Pembayaran"`). Jangan memakai data placeholder fiktif seperti `John Doe` atau buzzword pemasaran AI.

### D. Tiga UI State Wajib & Kontrol Interaktif (`R-24`, `R-26`, `R-27`, `R-33`)
- **Empty, Loading, & Error State (`R-27`)**: Setiap daftar data, tabel, papan kanban, dan widget analitik wajib menyediakan:
  1. **Loading State**: Indikator pemuatan yang jelas (skeleton/spinner berlabel teks).
  2. **Empty State**: Penjelasan mengapa data kosong (belum ada data vs hasil filter nihil) beserta tombol aksi lanjutan bila relevan.
  3. **Error State**: Pesan penyebab kegagalan dan tombol untuk mencoba ulang.
- **Nol Kontrol Mati (`R-24`, `R-26`)**: Dilarang menyertakan item sidebar, link, atau tombol yang tidak memiliki aksi/tujuan nyata.
- **Larangan Script Patching (`R-33`)**: Dilarang mengubah CSS atau file Vue menggunakan skrip penggantian string massal; lakukan perubahan langsung pada kode sumber komponen.

### E. Aksesibilitas Keyboard & Semantik HTML (`/antislop-human` `R-32`, `R-39`, `R-40`)
- **Semantik Kontrol (`R-39`)**: Aksi wajib memakai `<button type="button">` (atau `submit`), navigasi wajib memakai Inertia `<Link>` atau `<a href>`. Dilarang memasang `@click` pada `<div>` atau `<span>`.
- **Cursor & State (`R-39`)**: Setiap elemen interaktif aktif wajib memiliki `cursor: pointer`; elemen `disabled` wajib memiliki `cursor: not-allowed` dan memblokir eksekusi aksi di kode.
- **Navigasi Keyboard & Focus Ring (`R-32`)**: Dilarang menggunakan `outline: none` tanpa gaya `:focus-visible` pengganti berkontras minimal `3:1`. Seluruh dialog/modal wajib dapat ditutup menggunakan tombol `Escape`.
- **Label Form Terlihat (`R-39`)**: Setiap `<input>`, `<select>`, dan `<textarea>` wajib terhubung ke elemen `<label for="...">` yang terlihat (bukan hanya mengandalkan `placeholder`), dan pesan error harus berupa teks deskriptif, bukan warna merah saja.
- **Preferensi Gerak (`R-39`)**: Seluruh animasi/transisi non-esensial wajib menghormati `@media (prefers-reduced-motion: reduce)` (`MOTION 1`: fokus pada transisi state halus tanpa loop/pulse tak berujung).

---

## 3. Standar Keamanan Tingkat Matang (Enterprise Security & Financial Integrity)

Setiap endpoint Web, API, Service, dan Migration baru maupun perubahan pada kode eksisting wajib mematuhi 6 prinsip keamanan berikut:

### A. Defense-in-Depth Authorization (RBAC + Laravel Policy + Service Guard)
1. **Middleware Route Spesifik**: Dilarang meletakkan route administratif/finansial/HR (seperti pembayaran, manipulasi saldo cuti, manajemen hari libur, master vendor/rekening) di dalam grup role umum (`$essRoles`). Gunakan middleware `role:...` yang eksplisit dan terapkan `not_freelance` pada seluruh modul di luar absensi.
2. **Granular Laravel Policy (Anti-IDOR)**: Setiap controller yang menerima parameter model (`show`, `edit`, `update`, `destroy`, `cancel`, `print`, `exportPdf`) wajib memanggil `$this->authorize(...)` melalui class `Policy` (`PengajuanPolicy`, `CutiPolicy`, `TaskPolicy`, dll.) untuk memastikan caller adalah pemilik sah, target approver terkait, atau memiliki peran manajerial/HR/Finance yang berwenang.
3. **Identity Guard pada Workflow Engine**: Otorisasi persetujuan dokumen di `ApprovalService` wajib memverifikasi `id_karyawan_target === $actorKaryawanId` (atau delegasi sah yang aktif tercatat di `ApprovalGovernanceService`), **bukan** sekadar mengecek jabatan/role.

### B. Segregation of Duties (SoD) Mutlak
- Pembuat, pengaju, atau penerima manfaat dokumen (`id_pengaju`, `id_karyawan`, `created_by`, `id_user_pembuat`) **dilarang keras** menyetujui, memverifikasi, atau mencairkan dokumennya sendiri pada seluruh modul (`Pengajuan`, `Cuti`, `KoreksiAbsensi`, `DinasLuar`, `Pinjaman`, `Payroll`, `InternalTransfer`, `Invoice`).

### C. Verifikasi PIN Backend, Rate Limiting & Kredensial Aman
1. **Validasi PIN di Sisi Server**: Setiap aksi approval atau transaksi sensitif di Web maupun API wajib memvalidasi PIN 6-digit (atau sesi verifikasi PIN server berbatas waktu maksimal 5 menit) di backend. Validasi modal di Vue saja dianggap tidak aman.
2. **Brute-Force Protection**: Seluruh endpoint login, verifikasi PIN, dan aksi approval ber-PIN wajib dilindungi `throttle` dan lockout sementara setelah gagal berulang.
3. **Nol Kredensial Statis**: Dilarang membuat user baru dengan password/PIN hardcoded (seperti `'persija123'` atau `'123456'`). Gunakan generator acak satu kali dan wajibkan rotasi password/PIN pada login pertama.

### D. Atomicity, Ledger & Period Lock Integrity
1. **Transaksi & Pessimistic Locking**: Seluruh perubahan saldo cuti (`SaldoCuti`), alokasi anggaran (`BudgetMaster`), jurnal (`JurnalHeader`/`JurnalDetail`), status approval (`ApprovalProcess`), dan pembayaran wajib berjalan di dalam `DB::transaction()` dengan `lockForUpdate()`.
2. **Simetri Budget & Saldo**: Setiap proses `reject`, `cancel`, atau `destroy` sebelum pencairan wajib mengembalikan budget (`unCommitBudget`) atau saldo cuti secara atomik di dalam transaksi yang sama setelah validasi otorisasi lolos.
3. **Single Period Lock**: Seluruh mutasi akuntansi dan pembayaran wajib melewati validasi satu pintu `PeriodClosingService::checkClosed()` (`PeriodeClosing`) dan dilarang menggunakan tabel periode ganda.
4. **Sinkronisasi Jurnal & Audit Trail**: Perubahan pada komponen payroll/transaksi wajib menjaga keseimbangan Debit = Kredit sebelum posting GL, dan seluruh aksi finansial/governance kritis dicatat ke `ImmutableAuditService`.

### E. Penyimpanan Privat untuk Berkas Sensitif (PII & Keuangan)
- Dokumen pribadi karyawan (KTP, NPWP, BPJS, Kontrak Kerja, Surat Dokter/Cuti, Slip Gaji) dan dokumen bukti keuangan (bukti transfer, nota settlement) **wajib** disimpan di `Storage::disk('local')` (private) dan disajikan melalui controller terautentikasi yang memvalidasi `Policy`, bukan di `Storage::disk('public')`.

### F. Sanitasi Input, Template PDF & Kebijakan Jaringan Klien
1. **Anti-XSS & DomPDF Injection**: Semua variabel `{TAG}` dalam template kontrak/PDF wajib di-escape dengan `e()`, dan konten HTML dari WYSIWYG editor (TipTap) wajib disanitasi sebelum dirender melalui `v-html` atau `{!! ... !!}`.
2. **Nol Kebocoran Lokasi/IP ke Pihak Ketiga**: Dilarang melakukan fetch otomatis dari browser klien ke layanan eksternal (seperti `nominatim.openstreetmap.org` atau `ipapi.co`) untuk melacak posisi/IP user di halaman dashboard.

---

## 4. Protokol Pelacakan Progres & Delivery Gate

1. **Peta Pekerjaan & Tracker Resmi**:
   - Blueprint 3 Phase: [`05-WEB-IMPLEMENTATION-PLAN.md`](file:///Users/macbook/Application/docs/05-WEB-IMPLEMENTATION-PLAN.md)
   - Tracker Progres Live: [`06-PROGRESS-TASK-TRACKER.md`](file:///Users/macbook/Application/docs/06-PROGRESS-TASK-TRACKER.md)
2. **Kewajiban Update Tracker**:
   - Setiap kali satu task (`P1-Txx`, `P2-Txx`, atau `P3-Txx`) selesai dikerjakan dan lulus verifikasi (`php artisan test`, `npm run build`, atau check UI), **perbarui langsung** [`06-PROGRESS-TASK-TRACKER.md`](file:///Users/macbook/Application/docs/06-PROGRESS-TASK-TRACKER.md) pada turn yang sama (ubah `- [ ]` ke `- [x]`, update persentase fase, dan catat bukti verifikasi di Changelog).
3. **Mandatory Delivery Gate Sebelum Selesai**:
   - Pastikan 0 emoji tersisa pada seluruh perubahan.
   - Jalankan `php artisan test` dan `npm run build` tanpa error.
   - Untuk setiap perubahan UI, verifikasi Light Mode, Dark Mode, breakpoint mobile (`375px`), navigasi keyboard (`Tab`, `Enter`, `Escape`), serta keberadaan Empty/Loading/Error state sebelum melaporkan PASS (`R-35`).
