<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaryawanBiometric extends Model
{
    protected $table = 'tbl_karyawan_biometrics';

    protected $fillable = [
        'id_karyawan',
        'face_descriptor',
    ];

    protected $casts = [
        'face_descriptor' => 'encrypted',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function getDescriptor(string $platform): ?string
    {
        if (!$this->face_descriptor) return null;
        $data = json_decode($this->face_descriptor, true);
        if (!is_array($data)) return null;

        if (isset($data['web']) || isset($data['mobile'])) {
            return isset($data[$platform]) ? json_encode($data[$platform]) : null;
        }

        if (count($data) === 128 && $platform === 'web') return $this->face_descriptor;
        if (count($data) === 192 && $platform === 'mobile') return $this->face_descriptor;

        return null;
    }

    public function setDescriptor(string $platform, string $descriptorJson)
    {
        $existing = [];
        if ($this->face_descriptor) {
            $data = json_decode($this->face_descriptor, true);
            if (is_array($data)) {
                if (isset($data['web']) || isset($data['mobile'])) {
                    $existing = $data;
                } else {
                    if (count($data) === 128) $existing['web'] = $data;
                    if (count($data) === 192) $existing['mobile'] = $data;
                }
            }
        }
        $existing[$platform] = json_decode($descriptorJson, true);
        $this->face_descriptor = json_encode($existing);
        $this->save();
    }
}
