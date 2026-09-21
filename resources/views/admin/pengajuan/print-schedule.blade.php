<!DOCTYPE html>
<html>
<head>
    <title>Payment Schedule</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: sans-serif; font-size: 8pt; color: #333; line-height: 1.3; }
        
        /* Header similar to print.blade.php */
        .header-table { width: 100%; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-container { width: 65px; height: 65px; border: 1px solid #ddd; text-align: center; vertical-align: middle; background-color: #f9f9f9; }
        .logo-img { max-width: 60px; max-height: 60px; }
        .company-info { padding-left: 15px; vertical-align: top; }
        .company-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; color: #333; }
        .company-name { font-size: 9pt; font-weight: bold; margin-top: 2px; color: #444; }
        .company-addr { font-size: 7pt; color: #666; margin-top: 2px; }
        .meta-info { text-align: right; vertical-align: top; }
        .doc-title { font-size: 11pt; font-weight: bold; font-family: monospace; text-transform: uppercase; }

        /* Content Table */
        table.content-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 8pt; }
        table.content-table th { background-color: #f0f0f0; border: 1px solid #ccc; padding: 4px 6px; text-align: left; font-weight: bold; }
        table.content-table td { border: 1px solid #ccc; padding: 4px 6px; vertical-align: top; }
        
        .amount { text-align: right; font-family: monospace; white-space: nowrap; }
        .total-row td { background-color: #eee; font-weight: bold; }
        
        /* Signatures */
        .signatures { margin-top: 40px; width: 100%; text-align: center; }
        .sig-box { float: left; width: 33%; }
        .sig-line { margin-top: 50px; border-bottom: 1px solid #000; width: 80%; margin-left: auto; margin-right: auto; }
        .sig-label { font-size: 8pt; color: #555; }
        
        .footer { position: fixed; bottom: 0; left: 0; width: 100%; text-align: center; font-size: 6pt; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 70px;">
                <div class="logo-container">
                    @if(isset($company['logo_path']) && $company['logo_path'])
                        <img src="{{ $company['logo_path'] }}" class="logo-img">
                    @else
                        <span style="font-size:8pt; font-weight:bold; color:#ccc;">LOGO</span>
                    @endif
                </div>
            </td>
            <td class="company-info">
                <div class="company-title">Rencana Pembayaran</div>
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-addr">{{ $company['address'] }}</div>
            </td>
            <td class="meta-info">
                <div class="doc-title">PAYMENT SCHEDULE</div>
                <div style="font-size: 9pt; margin-top: 5px;">
                    Date: {{ $tgl_cetak->format('d/m/Y H:i') }}
                </div>
                <div style="font-size: 9pt;">
                    User: {{ $user->name }}
                </div>
            </td>
        </tr>
    </table>

    <table class="content-table">
        <thead>
            <tr>
                <th width="5%" style="text-align: center;">No</th>
                <th width="35%">Keterangan Pengajuan</th>
                <th width="25%">Penerima & Bank</th>
                <th width="20%" style="text-align: right;">Rencana Bayar</th>
                <th width="15%">Ket. Tambahan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $row)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>
                    <b>{{ $row['pengajuan']->nomor_pengajuan }}</b>
                    <span style="font-size: 7pt; border: 1px solid #999; padding: 1px 4px; border-radius: 2px; background-color: #f9f9f9;">{{ strtoupper($row['pengajuan']->status_global->value ?? $row['pengajuan']->status_global) }}</span>
                    <br>
                    <div style="margin-top:2px;">{{ $row['pengajuan']->judul_pengajuan }}</div>
                    <div style="font-size: 8pt; color: #666; margin-top:2px;">
                        Tagihan Asli: Rp {{ number_format($row['pengajuan']->total_nominal_diajukan, 0, ',', '.') }}
                    </div>
                </td>
                <td>
                    <b>{{ $row['penerima_nama'] }}</b><br>
                    <div style="font-size: 8pt; color: #555; margin-top:2px;">{{ $row['penerima_bank'] }}</div>
                </td>
                <td class="amount">
                    Rp {{ number_format($row['rencana_bayar'], 0, ',', '.') }}
                </td>
                <td>
                    {{ $row['keterangan'] }}
                    @if($row['rencana_bayar'] < $row['sisa_tagihan'])
                        <br><span style="font-size: 8pt; color: #d97706; font-weight:bold;">(Partial Payment)</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Rencana Pembayaran</td>
                <td class="amount">Rp {{ number_format($totalRencana, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="sig-box">
            <div class="sig-label">Dibuat Oleh,</div>
            <div class="sig-line"></div>
            <div style="font-weight:bold; margin-top:5px;">{{ $user->name }}</div>
        </div>
        <div class="sig-box">
            <div class="sig-label">Disetujui Oleh (Fin. Mgr),</div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <div class="sig-label">Diketahui Oleh (Direksi),</div>
            <div class="sig-line"></div>
        </div>
    </div>

    <div class="footer">
        Dicetak pada: {{ $tgl_cetak->format('d/m/Y H:i:s') }} | Sistem ERP Persija
    </div>

</body>
</html>
