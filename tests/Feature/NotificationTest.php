<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use Illuminate\Support\Str;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Departemen::create(['nama_departemen' => 'IT', 'kode_departemen' => 'IT']);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        Karyawan::create([
            'user_id' => $this->user->id,
            'id_departemen' => $dept->id,
            'nama_lengkap' => 'Notif Tester',
            'nomor_induk_karyawan' => 'NTF-001',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staff',
        ]);
    }

    public function test_user_can_fetch_notifications_and_unread_count()
    {
        // Seed database notification
        \DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\ApprovalPendingNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode([
                'type' => 'approval',
                'title' => 'Pengajuan Baru Menunggu Approval',
                'message' => 'Pengajuan dana sebesar Rp 5.000.000 membutuhkan approval Anda.',
                'link' => '/approval/42',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 1,
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_mark_notification_as_read()
    {
        $id = (string) Str::uuid();
        \DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\LeaveApprovedNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode(['title' => 'Cuti Disetujui', 'message' => 'Cuti Anda disetujui.']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/notifications/{$id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 0,
            ]);
    }

    public function test_user_can_get_and_update_notification_preferences()
    {
        $getResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $getResponse->assertStatus(200)
            ->assertJsonPath('data.approval_notifications', true);

        $updateResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'approval_notifications' => false,
                'task_notifications' => true,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.approval_notifications', false);
    }

    public function test_user_can_register_device_token_for_push()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/device-token', [
                'device_token' => 'fcm-token-test-123456',
                'device_type' => 'android',
                'device_name' => 'Pixel 8 Pro',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tbl_user_device_tokens', [
            'user_id' => $this->user->id,
            'device_token' => 'fcm-token-test-123456',
            'device_type' => 'android',
        ]);
    }
}
