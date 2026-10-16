<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class HariLiburController extends Controller
{
    public function index(Request $request)
    {
        $query = HariLibur::query();

        if ($request->has('search')) {
            $query->where('keterangan', 'like', '%' . $request->search . '%');
        }

        $holidays = $query->orderBy('tanggal', 'desc')->paginate(10);

        return Inertia::render('Admin/HariLibur/Index', [
            'holidays' => $holidays,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:tbl_hari_libur,tanggal',
            'keterangan' => 'required|string|max:255',
        ]);

        HariLibur::create($request->only(['tanggal', 'keterangan']));

        return redirect()->back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function update(Request $request, HariLibur $hariLibur)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:tbl_hari_libur,tanggal,' . $hariLibur->id,
            'keterangan' => 'required|string|max:255',
        ]);

        $hariLibur->update($request->only(['tanggal', 'keterangan']));

        return redirect()->back()->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(HariLibur $hariLibur)
    {
        $hariLibur->delete();
        return redirect()->back()->with('success', 'Hari libur berhasil dihapus.');
    }

    public function fetch(Request $request)
    {
        $year = (int) $request->input('year', date('Y'));
        
        try {
            // 1. Primary: Official Google Calendar iCal Feed
            $holidays = $this->fetchFromGoogleCalendar($year);
            $source = 'Google Calendar';

            // 2. Fallback if Google Calendar returns empty for that year
            if (empty($holidays)) {
                $holidays = $this->fetchFromFallbackApi($year);
                $source = 'Pusat Data Hari Libur Nasional';
            }

            if (empty($holidays)) {
                return redirect()->back()->with('error', "Tidak ditemukan data hari libur untuk tahun {$year}.");
            }

            $countNew = 0;
            $countUpdated = 0;

            foreach ($holidays as $date => $desc) {
                $holiday = HariLibur::where('tanggal', $date)->first();
                if (!$holiday) {
                    HariLibur::create([
                        'tanggal' => $date,
                        'keterangan' => $desc
                    ]);
                    $countNew++;
                } else {
                    if ($holiday->keterangan !== $desc) {
                        $holiday->update(['keterangan' => $desc]);
                        $countUpdated++;
                    }
                }
            }

            $message = "Berhasil menyinkronkan data hari libur tahun {$year} dari {$source}: {$countNew} data baru ditambahkan";
            if ($countUpdated > 0) {
                $message .= ", {$countUpdated} data diperbarui";
            }
            $message .= '.';

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal sinkronisasi hari libur: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage());
        }
    }

    /**
     * Fetch public holidays & cuti bersama directly from Google Calendar iCal feed
     */
    protected function fetchFromGoogleCalendar(int $year): array
    {
        try {
            $url = 'https://calendar.google.com/calendar/ical/id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics';
            
            $response = Http::timeout(15)->get($url);
            if (!$response->successful()) {
                return [];
            }

            $ics = $response->body();
            // RFC 5545 unfolding: remove newline followed by space or tab
            $unfolded = preg_replace("/\r?\n[ \t]/", "", $ics);

            preg_match_all("/BEGIN:VEVENT(.*?)END:VEVENT/s", $unfolded, $events);
            if (empty($events[1])) {
                return [];
            }

            $holidays = [];
            $targetYearStr = (string) $year;

            foreach ($events[1] as $ev) {
                preg_match("/DTSTART.*?:([0-9]{8})/", $ev, $dt);
                preg_match("/SUMMARY:(.*?)\r?\n/", $ev, $sum);
                preg_match("/DESCRIPTION:(.*?)\r?\n/", $ev, $desc);

                if (!empty($dt[1]) && !empty($sum[1])) {
                    $rawDate = $dt[1];
                    if (substr($rawDate, 0, 4) === $targetYearStr) {
                        $descriptionText = $desc[1] ?? '';
                        // Google marks official public holidays & joint leaves as "Hari libur nasional"
                        if (stripos($descriptionText, 'Hari libur nasional') !== false) {
                            $date = substr($rawDate, 0, 4) . '-' . substr($rawDate, 4, 2) . '-' . substr($rawDate, 6, 2);
                            $summary = trim(str_replace(['\\,', '\\;', '\\n', '\\N', '\\\\'], [',', ';', "\n", "\n", '\\'], $sum[1]));
                            // Clean up trailing "(belum pasti)" if any
                            $summary = trim(preg_replace('/\s*\(belum pasti\)\s*/i', '', $summary));

                            if (!isset($holidays[$date])) {
                                $holidays[$date] = $summary;
                            } else {
                                if (stripos($holidays[$date], $summary) === false) {
                                    $holidays[$date] .= ' / ' . $summary;
                                }
                            }
                        }
                    }
                }
            }

            ksort($holidays);
            return $holidays;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Google Calendar sync failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fallback to official SKB 3 Menteri JSON dataset or Nager.Date
     */
    protected function fetchFromFallbackApi(int $year): array
    {
        $holidays = [];

        // Fallback 1: SKB 3 Menteri JSON repository
        try {
            $resp1 = Http::timeout(10)->get("https://raw.githubusercontent.com/andifahruddinakas/api-hari-libur/main/data/{$year}.json");
            if ($resp1->successful()) {
                $data = $resp1->json();
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (!empty($item['date']) && !empty($item['description'])) {
                            $holidays[$item['date']] = trim($item['description']);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Fallback 1 (SKB 3 Menteri) failed: ' . $e->getMessage());
        }

        if (!empty($holidays)) {
            ksort($holidays);
            return $holidays;
        }

        // Fallback 2: Nager.Date API
        try {
            $resp2 = Http::timeout(10)->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/ID");
            if ($resp2->successful()) {
                $data = $resp2->json();
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (!empty($item['date']) && !empty($item['localName'])) {
                            $holidays[$item['date']] = trim($item['localName']);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Fallback 2 (Nager.Date) failed: ' . $e->getMessage());
        }

        ksort($holidays);
        return $holidays;
    }
}
