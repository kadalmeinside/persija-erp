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
            'nip' => $this->nip,
            'nama_lengkap' => $this->first_name . ' ' . $this->last_name,
            'jabatan' => $this->jabatan?->nama_jabatan, // Assuming relation exists
            'departemen' => $this->departemen?->nama_departemen, // Assuming relation exists
            'foto' => $this->foto_profil ? asset('storage/' . $this->foto_profil) : null,
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
