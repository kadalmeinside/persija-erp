<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IClockController extends Controller
{
    /**
     * Mesin absensi ADMS biasanya mengirim data absensi (dan data lainnya) ke endpoint ini.
     */
    public function cdata(Request $request)
    {
        Log::channel('single')->info('ICLOCK CDATA HIT', [
            'method' => $request->method(),
            'query'  => $request->query(),
            'body'   => $request->getContent(),
            'ip'     => $request->ip()
        ]);

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint initial handshake atau permintaan konfigurasi/perintah dari server.
     */
    public function getrequest(Request $request)
    {
        Log::channel('single')->info('ICLOCK GETREQUEST HIT', [
            'method' => $request->method(),
            'query'  => $request->query(),
            'body'   => $request->getContent(),
            'ip'     => $request->ip()
        ]);

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint untuk menanggapi perintah perangkat.
     */
    public function devicecmd(Request $request)
    {
        Log::channel('single')->info('ICLOCK DEVICECMD HIT', [
            'method' => $request->method(),
            'query'  => $request->query(),
            'body'   => $request->getContent(),
            'ip'     => $request->ip()
        ]);

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Cek koneksi dari mesin.
     */
    public function ping(Request $request)
    {
        Log::channel('single')->info('ICLOCK PING HIT', [
            'query' => $request->query(),
            'ip'    => $request->ip()
        ]);

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }
}
