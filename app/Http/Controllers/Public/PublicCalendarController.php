<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CompanyEvent;
use App\Models\HariLibur;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicCalendarController extends Controller
{
    /**
     * Display the public company calendar via secret token.
     */
    public function show(Request $request, string $token)
    {
        // 1. Defend against Automated AI Scrapers and Search Engine Crawlers
        $this->guardAgainstBots($request);

        // 2. Validate Token and Share Status
        $this->validateShareAccess($token);

        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        // 3. Fetch Holidays for given month & year
        $holidays = HariLibur::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->get()
            ->toBase()
            ->map(function ($holiday) {
                return [
                    'id' => 'holiday-' . $holiday->id,
                    'title' => $holiday->keterangan,
                    'start' => $holiday->tanggal->format('Y-m-d'),
                    'type' => 'holiday',
                    'color' => 'red',
                ];
            });

        // 4. Fetch Company Events / Room Bookings
        $showDetails = Setting::where('key', 'calendar_share_show_details')->value('value') !== '0';

        $companyEvents = CompanyEvent::where(function ($q) use ($year, $month) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

            $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
              ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
              ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                  $sub->where('start_date', '<=', $startOfMonth)
                      ->where('end_date', '>=', $endOfMonth);
              });
        })
        ->orderBy('start_date')
        ->get()
        ->toBase()
        ->map(function ($event) use ($showDetails) {
            return [
                'id' => 'event-' . $event->id,
                'title' => $showDetails ? $event->title : 'Jadwal Agenda / Pertemuan',
                'category' => $event->category ?? 'meeting',
                'start' => $event->start_date->format('Y-m-d H:i:s'),
                'end' => $event->end_date ? $event->end_date->format('Y-m-d H:i:s') : null,
                'location' => $event->location,
                'description' => $showDetails ? $event->description : null,
                'type' => 'event',
                'color' => $event->color ?: 'purple',
            ];
        });

        $events = $holidays->merge($companyEvents);

        $companyName = Setting::where('key', 'company_name')->value('value') ?? 'Persija ERP';
        $companyLogo = Setting::where('key', 'company_logo')->value('value');
        $companyLogoUrl = $companyLogo ? asset('storage/' . $companyLogo) : null;
        $shareUrl = url('/shared/calendar/' . $token);

        $rawSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(350)
            ->margin(1)
            ->generate($shareUrl);

        $response = Inertia::render('Public/Calendar', [
            'events' => $events,
            'currentMonth' => $month,
            'currentYear' => $year,
            'companyName' => $companyName,
            'companyLogoUrl' => $companyLogoUrl,
            'token' => $token,
            'qrCodeSvg' => 'data:image/svg+xml;base64,' . base64_encode($rawSvg),
            'rawSvg' => $rawSvg,
            'shareUrl' => $shareUrl,
        ])->toResponse($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * iCalendar (.ics) live subscription feed for external calendar apps.
     */
    public function ics(Request $request, string $token)
    {
        $this->guardAgainstBots($request);
        $this->validateShareAccess($token);

        $showDetails = Setting::where('key', 'calendar_share_show_details')->value('value') !== '0';
        $companyName = Setting::where('key', 'company_name')->value('value') ?? 'Persija ERP';

        $events = CompanyEvent::where('start_date', '>=', Carbon::now()->subMonths(3))
            ->where('start_date', '<=', Carbon::now()->addMonths(9))
            ->orderBy('start_date')
            ->get();

        $holidays = HariLibur::where('tanggal', '>=', Carbon::now()->subMonths(3))
            ->where('tanggal', '<=', Carbon::now()->addMonths(9))
            ->get();

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//" . $companyName . "//Company Calendar//ID\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "X-WR-CALNAME:Agenda " . $companyName . "\r\n";
        $ics .= "X-WR-TIMEZONE:Asia/Jakarta\r\n";

        foreach ($events as $event) {
            $dtStart = $event->start_date->format('Ymd\THis');
            $dtEnd = $event->end_date ? $event->end_date->format('Ymd\THis') : $dtStart;
            $title = $showDetails ? $event->title : 'Jadwal Agenda / Pertemuan';
            $summary = str_replace([",", ";", "\n"], ["\\,", "\\;", "\\n"], $title);
            $location = str_replace([",", ";", "\n"], ["\\,", "\\;", "\\n"], $event->location ?? '');

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:event-{$event->id}@{$request->getHost()}\r\n";
            $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $ics .= "DTSTART:{$dtStart}\r\n";
            $ics .= "DTEND:{$dtEnd}\r\n";
            $ics .= "SUMMARY:{$summary}\r\n";
            if ($location) {
                $ics .= "LOCATION:{$location}\r\n";
            }
            $ics .= "STATUS:CONFIRMED\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        foreach ($holidays as $holiday) {
            $dt = $holiday->tanggal->format('Ymd');
            $summary = str_replace([",", ";", "\n"], ["\\,", "\\;", "\\n"], $holiday->keterangan);

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:holiday-{$holiday->id}@{$request->getHost()}\r\n";
            $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $ics .= "DTSTART;VALUE=DATE:{$dt}\r\n";
            $ics .= "SUMMARY:{$summary}\r\n";
            $ics .= "STATUS:CONFIRMED\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="agenda-perusahaan.ics"',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet',
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
        ]);
    }

    /**
     * Guard against automated AI crawlers, scrapers, and aggressive bots.
     */
    protected function guardAgainstBots(Request $request): void
    {
        $userAgent = strtolower($request->header('User-Agent', ''));
        $blockedBots = [
            'gptbot',
            'chatgpt-user',
            'claudebot',
            'anthropic-ai',
            'ccbot',
            'bytespider',
            'google-extended',
            'perplexitybot',
            'diffbot',
            'cohere-ai',
            'facebookbot',
            'omgilibot',
            'yandexbot',
            'baiduspider',
        ];

        foreach ($blockedBots as $bot) {
            if (str_contains($userAgent, $bot)) {
                abort(403, 'Akses ditolak: Bot AI dan web crawler dilarang mengakses halaman ini.');
            }
        }
    }

    /**
     * Validate secret token and verify public share feature is enabled.
     */
    protected function validateShareAccess(string $token): void
    {
        $shareEnabled = Setting::where('key', 'calendar_share_enabled')->value('value') === '1';
        $validToken = Setting::where('key', 'calendar_share_token')->value('value');

        if (!$shareEnabled || empty($validToken) || !hash_equals((string) $validToken, (string) $token)) {
            abort(404, 'Kalender publik tidak ditemukan atau tautan telah dinonaktifkan oleh administrator.');
        }
    }
}
