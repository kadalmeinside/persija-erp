# 03 — Alur Persetujuan (ApprovalService)

`ApprovalService` adalah **jantung** sistem workflow PJH-ERP. Service ini mengelola approval multi-level untuk tiga jenis dokumen: **Pengajuan Dana**, **Cuti**, dan **Pinjaman**.

---

## 1. Konsep Dasar

### Approval Rules

Sebelum workflow bisa berjalan, **ApprovalRule** harus dikonfigurasi oleh admin:

```
ApprovalRule:
  id_departemen        → Berlaku untuk departemen ini
  tipe                 → 'Pengajuan' | 'Cuti' | 'Pinjaman'
  level_order          → Urutan langkah (1, 2, 3, ...)
  id_karyawan_approver → Karyawan yang harus menyetujui
  label_aksi           → Label tampilan (cth: "Verifikasi Manajer")
```

### Approval Process (Record per Langkah)

Setiap kali dokumen disubmit, `tbl_approval_process` diisi dengan satu baris per level rule:

```
ApprovalProcess:
  id_pengajuan        → FK ke PengajuanHeader (nullable)
  id_cuti             → FK ke PengajuanCuti (nullable)
  id_pinjaman         → FK ke Pinjaman (nullable)
  level_order         → Urutan langkah
  id_karyawan_target  → Approver yang ditunjuk (dari Rule)
  id_karyawan_action  → Approver yang benar-benar eksekusi
  label_aksi          → Label langkah
  status              → Waiting | Pending | Approved | Rejected | Skipped | Revision
  tgl_aksi            → Waktu eksekusi
  catatan             → Komentar approver
  uuid                → UUID unik (untuk verifikasi digital)
```

---

## 2. Alur `initApproval()`

Dipanggil saat dokumen pertama kali disubmit.

```
┌─────────────────────────────────────────────────────┐
│                  initApproval($model)                │
├─────────────────────────────────────────────────────┤
│ 1. Tentukan tipe (Pengajuan / Cuti / Pinjaman)      │
│ 2. Ambil ApprovalRules untuk departemen + tipe      │
│                                                     │
│ ┌── Untuk setiap Rule: ──────────────────────────┐  │
│ │  Apakah Approver == Pengaju?                   │  │
│ │  ├── YA  → Buat record status = 'Skipped'      │  │
│ │  │         (Auto-skip: conflict of interest)   │  │
│ │  └── TIDAK → Buat record                       │  │
│ │             ├── Pertama valid → status='Pending'│  │
│ │             │   + kirim notifikasi ke approver  │  │
│ │             └── Berikutnya → status='Waiting'   │  │
│ └────────────────────────────────────────────────┘  │
│                                                     │
│ Jika SEMUA step di-skip:                            │
│   → Auto-Approved (status langsung jadi Approved)   │
│                                                     │
│ Jika minimal 1 step valid:                          │
│   → Status = 'Pending Approval'                     │
└─────────────────────────────────────────────────────┘
```

### Kode Inti

```php
foreach ($rules as $rule) {
    $isSelfApproval = ($rule->id_karyawan_approver == $pengajuId);

    if ($isSelfApproval) {
        ApprovalProcess::create([..., 'status' => 'Skipped']);
    } else {
        $status = $firstActivated ? 'Waiting' : 'Pending';
        ApprovalProcess::create([..., 'status' => $status]);
        if (!$firstActivated) {
            // Notifikasi approver pertama yang valid
            $approverUser->notify(new PengajuanPendingNotification($model));
            $firstActivated = true;
        }
        $allSkipped = false;
    }
}

if ($allSkipped) { /* Auto-approve */ }
```

---

## 3. Alur `approve()`

Dipanggil saat approver menekan "Setujui".

```
┌─────────────────────────────────────────────────────┐
│               approve($model, $karyawanId)           │
├─────────────────────────────────────────────────────┤
│ 1. Cari step dengan status='Pending' (terendah)     │
│ 2. ⚠️  IDENTITY GUARD:                              │
│    Apakah actor == target approver?                  │
│    └── TIDAK → Lempar Exception (403)               │
│ 3. Update step: status='Approved' + uuid + waktu    │
│ 4. Hapus notifikasi approver ini                    │
│                                                     │
│ Cek step berikutnya yang bukan 'Skipped':           │
│ ├── ADA step berikutnya:                            │
│ │   → Aktifkan (status='Pending')                   │
│ │   → Kirim notifikasi ke approver berikutnya       │
│ └── TIDAK ADA → Final Approval:                     │
│     ├── PengajuanHeader → status = APPROVED         │
│     ├── PengajuanCuti   → status = Approved         │
│     │   + deductLeaveBalance() (kurangi saldo cuti) │
│     └── Pinjaman → status = APPROVED                │
│         + PinjamanService::createPaymentRequest()   │
└─────────────────────────────────────────────────────┘
```

---

## 4. Alur `reject()`

Dipanggil saat approver menolak dokumen.

```
┌───────────────────────────────────────────────────┐
│            reject($model, $karyawanId, $catatan)   │
├───────────────────────────────────────────────────┤
│ 1. Cari step Pending                              │
│ 2. ⚠️  IDENTITY GUARD (sama dengan approve)       │
│ 3. Update step: status='Rejected'                 │
│ 4. Update status dokumen → REJECTED               │
│ 5. Hapus notifikasi approver                      │
└───────────────────────────────────────────────────┘
```

---

## 5. Alur `revision()`

Approver meminta perbaikan tanpa menolak sepenuhnya.

```
┌───────────────────────────────────────────────────┐
│           revision($model, $karyawanId, $catatan)  │
├───────────────────────────────────────────────────┤
│ 1. Cari step Pending                              │
│ 2. ⚠️  IDENTITY GUARD                             │
│ 3. Update step: status='Revision'                 │
│ 4. Update dokumen → status = REVISION             │
│ 5. Kirim notifikasi ke PENGAJU (bukan approver)   │
│    (menginformasikan perlu perbaikan)             │
└───────────────────────────────────────────────────┘
```

### Saat Pengaju Re-submit Setelah Revision

```php
// Di PengajuanController::update()
if ($pengajuan->status_global === PengajuanStatus::REVISION) {
    // Hapus SEMUA step lama
    $pengajuan->approvalProcess()->delete();
    // Mulai approval dari awal
    $this->approvalService->initApproval($pengajuan->fresh());
}
```

---

## 6. Fitur Keamanan

### 6.1 Identity Guard

Setiap action (approve/reject/revision) **wajib** dilakukan oleh karyawan yang ditetapkan sebagai target langkah tersebut:

```php
if ($currentStep->id_karyawan_target != $karyawanIdAction) {
    throw new Exception("Anda tidak berwenang menyetujui langkah ini.");
}
```

> **Dampak:** Super Admin pun tidak bisa bypass — hanya approver yang ditunjuk dalam `ApprovalRule` yang bisa action.

### 6.2 Auto-Skip (Segregation of Duties)

Jika dalam `ApprovalRule`, seorang karyawan ditunjuk sebagai approver untuk dokumen yang **dia sendiri ajukan**, sistem otomatis men-skip langkah tersebut:

```
Contoh:
  Rule Level 1: Budi (Manajer) → approve
  Pengaju: Budi

  Hasil: Level 1 di-skip (status='Skipped', catatan='Auto-skipped: Conflict of Interest')
  Level 2 (jika ada): langsung jadi Pending
```

### 6.3 UUID Audit Trail

Setiap langkah yang di-approve mendapatkan UUID unik. UUID ini digunakan untuk:
- Verifikasi digital eksternal (link `/verify/{uuid}`)
- Audit trail yang tidak bisa dipalsukan

---

## 7. Status Flow Diagram

### PengajuanHeader

```
DRAFT
  └─→ (submit) ──→ PENDING APPROVAL
                       ├─→ (approve semua level) ──→ APPROVED
                       │                                 ├─→ (pay) ──→ PAID
                       │                                 └─→ (UangMuka + bayar) ──→ PAID
                       │                                                               └─→ (settle) ──→ VERIFICATION
                       │                                                                                    └─→ (verify) ──→ SETTLED
                       ├─→ (reject) ──────────────────→ REJECTED
                       ├─→ (revision) ─────────────────→ REVISION
                       │     └─→ (re-submit) ──→ PENDING APPROVAL
                       └─→ (cancel) ────────────────────→ CANCELLED
```

### PengajuanCuti

```
(submit) ──→ Pending
               ├─→ (approve) ──→ Approved → saldo cuti berkurang
               └─→ (reject)  ──→ Rejected
```

### Pinjaman

```
DRAFT ──→ PENDING_APPROVAL
             ├─→ (approve) ──→ APPROVED
             │                  └─→ otomatis buat PengajuanHeader (pencairan)
             └─→ (reject)  ──→ REJECTED
```

---

## 8. ApprovalService — API Reference

```php
// Inisialisasi workflow persetujuan
$approvalService->initApproval($model); // PengajuanHeader | PengajuanCuti | Pinjaman

// Menyetujui langkah saat ini
$approvalService->approve($model, $karyawanId, $catatan = null);

// Menolak langkah saat ini
$approvalService->reject($model, $karyawanId, $catatan);

// Meminta revisi pada pengaju
$approvalService->revision($model, $karyawanId, $catatan);
```

> Semua method `approve`, `reject`, dan `revision` dibungkus dalam **DB transaction** — jika terjadi error, perubahan dibatalkan secara otomatis.
