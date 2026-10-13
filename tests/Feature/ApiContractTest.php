<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\JenisCuti;
use App\Models\PengajuanCuti;
use Spatie\Permission\Models\Role;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    protected $direkturUser;
    protected $stafUser;
    protected $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Departemen::create([
            'nama_departemen' => 'Executive',
            'kode_departemen' => 'EXEC',
        ]);

        Role::firstOrCreate(['name' => 'Direktur']);
        Role::firstOrCreate(['name' => 'Staf']);

        $this->direkturUser = User::factory()->create([
            'name' => 'Direktur Utama',
            'email' => 'direktur@persija.id',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $this->direkturUser->assignRole('Direktur');

        Karyawan::create([
            'user_id' => $this->direkturUser->id,
            'id_departemen' => $this->dept->id,
            'nama_lengkap' => 'Direktur Utama',
            'nomor_induk_karyawan' => 'DIR-001',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Direktur',
        ]);

        $this->stafUser = User::factory()->create([
            'name' => 'Staf Biasa',
            'email' => 'staf@persija.id',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $this->stafUser->assignRole('Staf');

        Karyawan::create([
            'user_id' => $this->stafUser->id,
            'id_departemen' => $this->dept->id,
            'nama_lengkap' => 'Staf Biasa',
            'nomor_induk_karyawan' => 'STF-001',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf IT',
        ]);
    }

    public function test_api_login_contract_schema()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'direktur@persija.id',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'has_pin',
                        'karyawan' => [
                            'id',
                            'nip',
                            'nama_lengkap',
                            'jabatan',
                            'departemen',
                            'status',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertEquals('direktur@persija.id', $response->json('data.user.email'));
    }

    public function test_api_user_profile_contract_schema()
    {
        $response = $this->actingAs($this->direkturUser, 'sanctum')
            ->getJson('/api/v1/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'has_pin',
                    'karyawan' => [
                        'id',
                        'nip',
                        'nama_lengkap',
                        'jabatan',
                        'departemen',
                    ],
                ],
            ]);
    }

    public function test_api_cuti_all_contract_schema_for_authorized_role()
    {
        $jenis = JenisCuti::create([
            'nama_cuti' => 'Tahunan',
            'jatah_hari' => 12,
            'status' => 'Aktif',
        ]);

        $karyawan = $this->direkturUser->karyawan;

        PengajuanCuti::create([
            'id_karyawan' => $karyawan->id,
            'id_jenis_cuti' => $jenis->id,
            'tgl_mulai' => now()->toDateString(),
            'tgl_selesai' => now()->toDateString(),
            'jumlah_hari' => 1,
            'alasan' => 'Libur tahunan',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($this->direkturUser, 'sanctum')
            ->getJson('/api/v1/cuti/all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nama_karyawan',
                        'nip',
                        'jabatan',
                        'departemen',
                        'jenis_cuti',
                        'tgl_mulai',
                        'tgl_selesai',
                        'jumlah_hari',
                        'status',
                    ],
                ],
                'today_leaves',
                'meta' => [
                    'total_on_leave_today',
                    'total_filtered',
                    'current_page',
                    'last_page',
                ],
            ]);
    }

    public function test_api_approvals_contract_schema()
    {
        $response = $this->actingAs($this->direkturUser, 'sanctum')
            ->getJson('/api/v1/approvals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_api_absensi_today_contract_schema()
    {
        $response = $this->actingAs($this->stafUser, 'sanctum')
            ->getJson('/api/v1/absensi/today');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'clock_in',
                    'clock_out',
                    'status',
                ],
            ]);
    }
}
