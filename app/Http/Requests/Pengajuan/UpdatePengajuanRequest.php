<?php

namespace App\Http\Requests\Pengajuan;

use App\Enums\PaymentMethod;
use App\Enums\PengajuanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pengaju' => 'required|integer|exists:tbl_karyawan,id',
            'id_departemen' => 'required|integer|exists:tbl_departemen,id',
            'judul_pengajuan' => 'required|string|max:255',
            'tgl_pengajuan' => 'required|date',
            'tipe_pengajuan' => ['required', new Enum(PengajuanType::class)],
            'metode_pembayaran' => ['required', new Enum(PaymentMethod::class)],
            'attachment' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'catatan_header' => 'nullable|string',
            'total_nominal_diajukan' => 'required|numeric|min:1',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.deskripsi_item' => 'required|string|max:255',
            'items.*.nominal_item' => 'required|numeric|min:1',
            'items.*.id_program' => 'required|integer|exists:tbl_program_kerja,id',
            'items.*.id_akun' => 'required|integer|exists:tbl_akun_gl,id',
            'items.*.id_tax_type' => 'nullable|integer|exists:tbl_tax_types,id',
            
            // Beneficiary
            'id_vendor_penerima' => 'nullable',
            'id_karyawan_penerima' => 'nullable',
            'bank_tujuan' => 'nullable|string|max:50',
            'no_rek_tujuan' => 'nullable|string|max:50',
            'atas_nama_tujuan' => 'nullable|string|max:100',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();
            
            // Conditional Logic for Beneficiary (Same as Store)
            if (isset($data['metode_pembayaran']) && $data['metode_pembayaran'] === PaymentMethod::TRANSFER->value) {
                if (isset($data['tipe_pengajuan']) && $data['tipe_pengajuan'] === PengajuanType::LANGSUNG->value) {
                    $isKaryawan = $this->input('sub_tipe_penerima') === 'Karyawan' || (!empty($data['id_karyawan_penerima']) && empty($data['id_vendor_penerima']));
                    
                    if ($isKaryawan) {
                        if (empty($data['id_karyawan_penerima'])) {
                            $validator->errors()->add('id_karyawan_penerima', 'Penerima Karyawan wajib diisi.');
                        }
                    } else {
                        if (empty($data['id_vendor_penerima'])) {
                            $validator->errors()->add('id_vendor_penerima', 'Penerima Vendor wajib diisi.');
                        }
                    }
                } else {
                    if (empty($data['id_karyawan_penerima'])) {
                        $validator->errors()->add('id_karyawan_penerima', 'Penerima Karyawan wajib diisi untuk Uang Muka.');
                    }
                }
            }
        });
    }
}
