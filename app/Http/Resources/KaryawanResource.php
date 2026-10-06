<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KaryawanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nip' => $this->nomor_induk_karyawan,
            'nama_lengkap' => $this->nama_lengkap,
            'jabatan' => $this->jabatan,
            'departemen' => $this->departemen ? $this->departemen->nama_departemen : null,
            'tgl_bergabung' => $this->tgl_bergabung,
            'status' => $this->status_karyawan,
            'foto' => $this->foto_url,
            'face_descriptor' => $this->biometric ? $this->biometric->face_descriptor : null,
            'is_strict_location' => (bool) $this->is_strict_location,
            'lokasi_kantor' => $this->lokasiKantor ? [
                'nama' => $this->lokasiKantor->nama_kantor,
                'latitude' => $this->lokasiKantor->latitude,
                'longitude' => $this->lokasiKantor->longitude,
                'radius' => $this->lokasiKantor->radius_meter,
            ] : null,
        ];
    }
}
