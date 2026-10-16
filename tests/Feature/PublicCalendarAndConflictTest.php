<?php

namespace Tests\Feature;

use App\Models\CompanyEvent;
use App\Models\HariLibur;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicCalendarAndConflictTest extends TestCase
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

    public function test_room_booking_conflict_is_prevented(): void
    {
        // 1. Create first meeting in "Ruang Rapat Utama" from 10:00 to 12:00
        $response1 = $this->actingAs($this->admin)->post(route('admin.company-events.store'), [
            'title' => 'Meeting Internal Tim A',
            'start_date' => '2026-11-10 10:00:00',
            'end_date' => '2026-11-10 12:00:00',
            'location' => 'Ruang Rapat Utama (Lt. 2)',
            'description' => 'Pembahasan project Q4'
        ]);
        $response1->assertRedirect();
        $this->assertDatabaseHas('company_events', [
            'title' => 'Meeting Internal Tim A',
            'location' => 'Ruang Rapat Utama (Lt. 2)'
        ]);

        // 2. Attempt to create overlapping meeting in the same room (11:00 to 13:00)
        $response2 = $this->actingAs($this->admin)->post(route('admin.company-events.store'), [
            'title' => 'Meeting Klien Tim B',
            'start_date' => '2026-11-10 11:00:00',
            'end_date' => '2026-11-10 13:00:00',
            'location' => 'ruang rapat utama (lt. 2)', // Case-insensitive check
            'description' => 'Meeting dengan vendor'
        ]);
        $response2->assertSessionHasErrors('location');

        // 3. Attempt in a DIFFERENT room at the same time succeeds
        $response3 = $this->actingAs($this->admin)->post(route('admin.company-events.store'), [
            'title' => 'Meeting Klien Tim B',
            'start_date' => '2026-11-10 11:00:00',
            'end_date' => '2026-11-10 13:00:00',
            'location' => 'Ruang Meeting Direksi',
            'description' => 'Meeting dengan vendor di ruangan lain'
        ]);
        $response3->assertRedirect();
        $this->assertDatabaseHas('company_events', [
            'title' => 'Meeting Klien Tim B',
            'location' => 'Ruang Meeting Direksi'
        ]);
    }

    public function test_event_can_be_updated_without_self_conflict(): void
    {
        $event = CompanyEvent::create([
            'title' => 'Rapat Awal',
            'start_date' => '2026-11-15 09:00:00',
            'end_date' => '2026-11-15 11:00:00',
            'location' => 'Ruang Rapat Utama',
            'created_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.company-events.update', $event->id), [
            'title' => 'Rapat Awal Diperbarui',
            'start_date' => '2026-11-15 09:00:00',
            'end_date' => '2026-11-15 11:00:00',
            'location' => 'Ruang Rapat Utama',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('company_events', [
            'id' => $event->id,
            'title' => 'Rapat Awal Diperbarui'
        ]);
    }

    public function test_public_calendar_can_be_viewed_with_secret_token_and_headers(): void
    {
        $token = 'test-secret-calendar-token-12345';
        Setting::updateOrCreate(['key' => 'calendar_share_token'], ['value' => $token]);
        Setting::updateOrCreate(['key' => 'calendar_share_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'calendar_share_show_details'], ['value' => '1']);

        HariLibur::create([
            'tanggal' => '2026-11-01',
            'keterangan' => 'Libur Nasional Uji Coba'
        ]);

        CompanyEvent::create([
            'title' => 'Townhall Meeting Persija',
            'start_date' => '2026-11-20 14:00:00',
            'end_date' => '2026-11-20 16:00:00',
            'location' => 'Auditorium Utama',
            'created_by' => $this->admin->id
        ]);

        // Unauthenticated guest request with secret token
        $response = $this->get(route('public.calendar.show', [
            'token' => $token,
            'month' => 11,
            'year' => 2026
        ]));

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Calendar')
            ->has('qrCodeSvg')
            ->has('shareUrl')
            ->where('token', $token)
        );

        // Admin Calendar index also receives QR code in shareSettings
        $adminResp = $this->actingAs($this->admin)->get(route('admin.calendar.index'));
        $adminResp->assertInertia(fn ($page) => $page
            ->component('Admin/Calendar/Index')
            ->has('shareSettings.qrCodeSvg')
            ->has('shareSettings.rawSvg')
        );

        // Invalid token returns 404
        $invalidResponse = $this->get(route('public.calendar.show', ['token' => 'wrong-token-abc']));
        $invalidResponse->assertNotFound();
    }

    public function test_public_calendar_blocks_ai_scrapers(): void
    {
        $token = 'valid-token-for-ai-test';
        Setting::updateOrCreate(['key' => 'calendar_share_token'], ['value' => $token]);
        Setting::updateOrCreate(['key' => 'calendar_share_enabled'], ['value' => '1']);

        // Request with GPTBot User-Agent
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)'
        ])->get(route('public.calendar.show', ['token' => $token]));

        $response->assertForbidden();

        // Request with ClaudeBot User-Agent
        $claudeResponse = $this->withHeaders([
            'User-Agent' => 'ClaudeBot/1.0; +claudebot@anthropic.com'
        ])->get(route('public.calendar.show', ['token' => $token]));

        $claudeResponse->assertForbidden();
    }

    public function test_admin_can_update_share_settings_and_regenerate_token(): void
    {
        $initialToken = 'initial-token-123';
        Setting::updateOrCreate(['key' => 'calendar_share_token'], ['value' => $initialToken]);
        Setting::updateOrCreate(['key' => 'calendar_share_enabled'], ['value' => '1']);

        // Update settings: disable public link
        $resp1 = $this->actingAs($this->admin)->post(route('admin.calendar.share-settings'), [
            'enabled' => false,
            'show_details' => false
        ]);
        $resp1->assertRedirect();
        $this->assertEquals('0', Setting::where('key', 'calendar_share_enabled')->value('value'));

        // Public access should now be 404 since disabled
        $respDisabled = $this->get(route('public.calendar.show', ['token' => $initialToken]));
        $respDisabled->assertNotFound();

        // Regenerate token
        $resp2 = $this->actingAs($this->admin)->post(route('admin.calendar.regenerate-share-token'));
        $resp2->assertRedirect();

        $newToken = Setting::where('key', 'calendar_share_token')->value('value');
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($initialToken, $newToken);
    }

    public function test_public_calendar_ics_feed(): void
    {
        $token = 'test-ics-token-feed-789';
        Setting::updateOrCreate(['key' => 'calendar_share_token'], ['value' => $token]);
        Setting::updateOrCreate(['key' => 'calendar_share_enabled'], ['value' => '1']);

        CompanyEvent::create([
            'title' => 'Rapat Koordinasi ICS',
            'start_date' => Carbon::now()->addDays(5)->format('Y-m-d 09:00:00'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d 11:00:00'),
            'location' => 'Studio Press',
            'created_by' => $this->admin->id
        ]);

        $response = $this->get(route('public.calendar.ics', ['token' => $token]));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
        $this->assertStringContainsString('Rapat Koordinasi ICS', $response->getContent());
    }
}
