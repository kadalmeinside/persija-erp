<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // Pastikan Spatie Trait ada
use App\Models\Karyawan; // <-- TAMBAHKAN INI

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // <-- Pastikan HasRoles ada

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Mendefinisikan relasi one-to-one ke Karyawan.
     * Ini akan memperbaiki error di PengajuanController.
     */
    public function karyawan()
    {
        // 'user_id' adalah foreign key di tabel 'tbl_karyawan'
        // 'id' adalah primary key di tabel 'users'
        return $this->hasOne(Karyawan::class, 'user_id', 'id');
    }
}