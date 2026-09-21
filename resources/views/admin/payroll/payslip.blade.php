<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $karyawan->nama_lengkap }}</title>
    <style>
        @page { margin: 10mm; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #333333;
            line-height: 1.3;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        td {
            vertical-align: top;
            padding: 3px 0;
        }
        
        /* HEADER STYLES (Identical to Pengajuan) */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-container {
            width: 65px;
            height: 65px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
            background-color: #f9f9f9;
        }
        .logo-img {
            max-width: 60px;
            max-height: 60px;
        }
        .company-info {
            padding-left: 15px;
            vertical-align: top;
        }
        .company-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
        }
        .company-name {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 2px;
            color: #444;
        }
        .company-addr {
            font-size: 8pt;
            color: #666;
            margin-top: 2px;
        }
        .meta-info {
            text-align: right;
            vertical-align: top;
        }
        .ref-no {
            font-size: 10pt;
            font-weight: bold;
        }

        /* BODY STYLES */
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #222222;
            border-bottom: 1px solid #dddddd;
            padding-bottom: 5px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .thp-box {
            background-color: #f8f9fa;
            border: 1px solid #dddddd;
            padding: 12px;
            margin-top: 20px;
            margin-bottom: 25px;
        }

        .footer-note {
            font-size: 7pt;
            color: #777777;
            text-align: center;
            border-top: 1px solid #eeeeee;
            padding-top: 8px;
            margin-top: 30px;
            line-height: 1.4;
        }
        
        .label-cell {
            width: 85px;
            color: #555555;
            font-size: 8.5pt;
        }
        .colon-cell {
            width: 15px;
            color: #555555;
        }
        .value-cell {
            font-weight: bold;
            font-size: 9pt;
        }
    </style>
</head>
<body>

    <!-- HEADER (Identical layout to Pengajuan Dana) -->
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
                <div class="company-title">Slip Gaji Karyawan</div>
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-addr">{{ $company['address'] }}</div>
            </td>
            <td class="meta-info" style="white-space: nowrap;">
                <div class="ref-no">Periode: {{ \Carbon\Carbon::parse($payroll->bulan_periode . '-01')->translatedFormat('F Y') }}</div>
                <div style="font-size: 7.5pt; margin-top: 3px; color: #777;">Cetak: {{ $tgl_cetak->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <!-- Employee Information -->
    <table style="margin-bottom: 25px;">
        <tr>
            <td width="48%">
                <table>
                    <tr>
                        <td class="label-cell">Nama Lengkap</td>
                        <td class="colon-cell">:</td>
                        <td class="value-cell">{{ $karyawan->nama_lengkap }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">ID / NIK</td>
                        <td class="colon-cell">:</td>
                        <td class="value-cell">{{ $karyawan->nomor_induk_karyawan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Jabatan</td>
                        <td class="colon-cell">:</td>
                        <td class="value-cell">{{ $karyawan->jabatan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Departemen</td>
                        <td class="colon-cell">:</td>
                        <td class="value-cell">{{ $karyawan->departemen->nama_departemen ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            <td width="4%"></td>
            <td width="48%">
                <div style="margin-left: auto; width: 140px; border: 1px solid #e0e0e0; background-color: #fcfcfc; padding: 8px 10px; border-radius: 4px; text-align: right;">
                    <div style="font-size: 6.5pt; color: #888; text-transform: uppercase; letter-spacing: 0.5px;">Metode Bayar</div>
                    <div style="font-size: 8.5pt; font-weight: bold; color: #333; margin-top: 1px;">Transfer Bank</div>
                    <div style="font-size: 8.5pt; font-weight: bold; color: #333; margin-top: 1px;">{{ $karyawan->primary_bank->nama_bank ?? '-' }}</div>
                    
                    <div style="border-top: 1px dashed #ddd; margin-top: 6px; padding-top: 6px;">
                        <div style="font-size: 6.5pt; color: #888; text-transform: uppercase; letter-spacing: 0.5px;">Nomor Rekening</div>
                        <div style="font-size: 9pt; font-weight: bold; font-family: monospace; color: #111; margin-top: 2px; letter-spacing: 1px;">{{ $karyawan->primary_bank->nomor_rekening ?? '-' }}</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @php
        $total_pendapatan = $detail->gaji_pokok + $detail->total_tunjangan + $detail->lembur + $detail->honorarium + $pendapatan_tambahan;
        $total_potongan = $detail->total_potongan;
    @endphp

    <!-- Financial Details -->
    <table>
        <tr>
            <!-- EARNINGS -->
            <td width="48%">
                <div class="section-title">PENERIMAAN</div>
                <table style="font-size:8.5pt;">
                    @if($detail->gaji_pokok > 0)
                    <tr>
                        <td>Gaji Pokok</td>
                        <td align="right">{{ number_format($detail->gaji_pokok, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    
                    @if($detail->total_tunjangan > 0)
                    <tr>
                        <td>Tunjangan</td>
                        <td align="right">{{ number_format($detail->total_tunjangan, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    
                    @if($detail->lembur > 0)
                    <tr>
                        <td>Uang Lembur</td>
                        <td align="right">{{ number_format($detail->lembur, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    
                    @if($detail->honorarium > 0)
                    <tr>
                        <td>Honorarium</td>
                        <td align="right">{{ number_format($detail->honorarium, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    @foreach($rincian as $item)
                        @if(isset($item['type']) && isset($item['amount']) && $item['amount'] > 0)
                        <tr>
                            <td>{{ $item['keterangan'] ?? $item['type'] }}</td>
                            <td align="right">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    @endforeach
                    
                    <!-- Spacing -->
                    <tr><td colspan="2" style="height:15px;"></td></tr>
                    
                    <tr>
                        <td style="border-top: 1px solid #ddd; padding-top: 6px; font-weight:bold; font-size:9pt;">Total Penerimaan</td>
                        <td style="border-top: 1px solid #ddd; padding-top: 6px; font-weight:bold; font-size:9pt;" align="right">{{ number_format($total_pendapatan, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
            
            <td width="4%"></td>
            
            <!-- DEDUCTIONS -->
            <td width="48%">
                <div class="section-title">POTONGAN</div>
                <table style="font-size:8.5pt;">
                    @if($detail->total_potongan > 0)
                        <tr>
                            <td>Total Potongan</td>
                            <td align="right">- {{ number_format($detail->total_potongan, 0, ',', '.') }}</td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="2" style="color:#999; font-style:italic;">Tidak ada potongan</td>
                        </tr>
                    @endif
                    
                    <!-- Spacing -->
                    <tr><td colspan="2" style="height:15px;"></td></tr>

                    <tr>
                        <td style="border-top: 1px solid #ddd; padding-top: 6px; font-weight:bold; font-size:9pt;">Total Potongan</td>
                        <td style="border-top: 1px solid #ddd; padding-top: 6px; font-weight:bold; font-size:9pt;" align="right">{{ number_format($total_potongan, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TAKE HOME PAY -->
    <div class="thp-box">
        <table>
            <tr>
                <td width="50%" style="vertical-align:middle;">
                    <span style="font-size:10pt; color:#555; font-weight:bold;">TAKE HOME PAY</span>
                </td>
                <td width="50%" align="right" style="vertical-align:middle;">
                    <span style="font-size:14pt; font-weight:bold; color:#111;">Rp {{ number_format($detail->gaji_bersih, 0, ',', '.') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER NOTE -->
    <div class="footer-note">
        <strong>RAHASIA / CONFIDENTIAL</strong><br>
        Slip gaji ini adalah dokumen rahasia. Informasi di dalamnya hanya ditujukan kepada pihak yang bersangkutan.<br>
        Dokumen ini dibuat secara otomatis oleh sistem komputer dan sah tanpa stempel basah.
        <br><br>
        Dicetak pada: {{ $tgl_cetak->format('d/m/Y H:i') }}
    </div>

</body>
</html>
