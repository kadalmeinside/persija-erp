<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiResource extends JsonResource
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
            'date' => $this->tanggal,
            'clock_in' => $this->waktu_masuk,
            'clock_out' => $this->waktu_keluar,
            'status' => $this->status_kehadiran,
            'lat_masuk' => $this->lat_masuk,
            'lng_masuk' => $this->lng_masuk,
            'lat_keluar' => $this->lat_keluar,
            'lng_keluar' => $this->lng_keluar,
            'foto_masuk' => $this->foto_masuk ? asset('storage/' . $this->foto_masuk) : null,
            'foto_keluar' => $this->foto_keluar ? asset('storage/' . $this->foto_keluar) : null,
            'is_dinas_luar' => $this->is_dinas_luar ? true : false,
            'catatan' => $this->catatan,
        ];
    }
}
