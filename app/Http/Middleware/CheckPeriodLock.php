<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPeriodLock
{
    /**
     * Handle an incoming request.
     *
     * Memeriksa apakah tanggal transaksi berada dalam periode yang sudah ditutup.
     * Jika tanggal tidak ditemukan di request (pada operasi write), request akan ditolak
     * agar tidak ada transaksi yang bisa bypass period lock secara diam-diam.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $dateField  Nama field tanggal utama yang akan dicek (default: tgl_transaksi)
     */
    public function handle(Request $request, Closure $next, $dateField = 'tgl_transaksi')
    {
        // 1. Coba ambil tanggal dari field utama
        $date = $request->input($dateField);

        // 2. Jika tidak ada, coba field nama umum lainnya
        if (!$date) {
            $date = $request->input('tgl_jurnal')
                 ?? $request->input('tgl_invoice')
                 ?? $request->input('tgl_bayar')
                 ?? $request->input('tgl_pengajuan')
                 ?? $request->input('tanggal');
        }

        // 3. Jika tanggal masih tidak ditemukan pada operasi write (non-GET),
        //    tolak request untuk mencegah bypass period lock secara diam-diam.
        if (!$date) {
            if (!$request->isMethod('GET')) {
                $message = 'Validasi periode gagal: tanggal transaksi tidak ditemukan dalam request.';
                if ($request->wantsJson()) {
                    return response()->json(['message' => $message], 422);
                }
                return redirect()->back()->with('error', $message);
            }
            // GET request tanpa tanggal → izinkan (misalnya halaman index)
            return $next($request);
        }

        // 4. Cek apakah tanggal jatuh dalam periode yang sudah ditutup
        $isClosed = \App\Models\AccountingPeriod::where('is_closed', true)
            ->whereDate('tanggal_mulai', '<=', $date)
            ->whereDate('tanggal_selesai', '>=', $date)
            ->exists();

        if ($isClosed) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Periode akuntansi untuk tanggal ini sudah ditutup.'], 403);
            }
            return redirect()->back()->with('error', 'Transaksi ditolak: Periode akuntansi untuk tanggal ' . $date . ' sudah ditutup (Closed).');
        }

        return $next($request);
    }
}
