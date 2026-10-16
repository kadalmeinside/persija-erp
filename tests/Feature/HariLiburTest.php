<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HariLiburTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_admin_can_view_hari_libur_index(): void
    {
        HariLibur::create([
            'tanggal' => '2026-01-01',
            'keterangan' => 'Tahun Baru 2026'
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.hari-libur.index'));
        $response->assertOk();
    }

    public function test_admin_can_store_and_update_and_delete_hari_libur(): void
    {
        // Store
        $response = $this->actingAs($this->admin)->post(route('admin.hari-libur.store'), [
            'tanggal' => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('tbl_hari_libur', [
            'keterangan' => 'Hari Kemerdekaan RI'
        ]);
        $holiday = HariLibur::where('keterangan', 'Hari Kemerdekaan RI')->firstOrFail();
        $this->assertEquals('2026-08-17', $holiday->tanggal->format('Y-m-d'));

        // Update
        $response = $this->actingAs($this->admin)->put(route('admin.hari-libur.update', $holiday->id), [
            'tanggal' => '2026-08-17',
            'keterangan' => 'HUT Kemerdekaan RI ke-81'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('tbl_hari_libur', [
            'id' => $holiday->id,
            'keterangan' => 'HUT Kemerdekaan RI ke-81'
        ]);

        // Destroy
        $response = $this->actingAs($this->admin)->delete(route('admin.hari-libur.destroy', $holiday->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('tbl_hari_libur', ['id' => $holiday->id]);
    }

    public function test_admin_can_sync_hari_libur_from_google_calendar(): void
    {
        $sampleIcs = "BEGIN:VCALENDAR\r\n" .
            "PRODID:-//Google Inc//Google Calendar 70.9054//EN\r\n" .
            "VERSION:2.0\r\n" .
            "BEGIN:VEVENT\r\n" .
            "DTSTART;VALUE=DATE:20260101\r\n" .
            "SUMMARY:Hari Tahun Baru\r\n" .
            "DESCRIPTION:Hari libur nasional\r\n" .
            "END:VEVENT\r\n" .
            "BEGIN:VEVENT\r\n" .
            "DTSTART;VALUE=DATE:20260501\r\n" .
            "SUMMARY:Hari Buruh Internasional\r\n" .
            "DESCRIPTION:Hari libur nasional\r\n" .
            "END:VEVENT\r\n" .
            "BEGIN:VEVENT\r\n" .
            "DTSTART;VALUE=DATE:20261231\r\n" .
            "SUMMARY:Malam Tahun Baru\r\n" .
            "DESCRIPTION:Perayaan\r\n" .
            "END:VEVENT\r\n" .
            "END:VCALENDAR";

        Http::fake([
            'https://calendar.google.com/*' => Http::response($sampleIcs, 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.hari-libur.fetch'), [
            'year' => 2026
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Only national holidays should be added, not Perayaan
        $this->assertDatabaseHas('tbl_hari_libur', [
            'keterangan' => 'Hari Tahun Baru'
        ]);
        $this->assertDatabaseHas('tbl_hari_libur', [
            'keterangan' => 'Hari Buruh Internasional'
        ]);
        $this->assertDatabaseMissing('tbl_hari_libur', [
            'keterangan' => 'Malam Tahun Baru'
        ]);

        $h1 = HariLibur::where('keterangan', 'Hari Tahun Baru')->firstOrFail();
        $this->assertEquals('2026-01-01', $h1->tanggal->format('Y-m-d'));
    }

    public function test_admin_can_sync_hari_libur_from_fallback_when_google_fails(): void
    {
        // Fail Google Calendar request
        Http::fake([
            'https://calendar.google.com/*' => Http::response('Server Error', 500),
            'https://raw.githubusercontent.com/*' => Http::response([
                ['date' => '2026-06-01', 'description' => 'Hari Lahir Pancasila']
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.hari-libur.fetch'), [
            'year' => 2026
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tbl_hari_libur', [
            'keterangan' => 'Hari Lahir Pancasila'
        ]);

        $h = HariLibur::where('keterangan', 'Hari Lahir Pancasila')->firstOrFail();
        $this->assertEquals('2026-06-01', $h->tanggal->format('Y-m-d'));
    }
}
