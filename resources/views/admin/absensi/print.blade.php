<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi - {{ $date }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
        }
        .status-badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-Hadir { background-color: #d1fae5; color: #065f46; }
        .status-Terlambat { background-color: #fef3c7; color: #92400e; }
        .status-Lupa { background-color: #fee2e2; color: #b91c1c; } /* Lupa Checkout */
        .status-Luar { background-color: #e0e7ff; color: #3730a3; } /* Luar Kantor */
        .status-Tidak { background-color: #f3f4f6; color: #374151; } /* Tidak Hadir */
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>Rekap Absensi Karyawan</h2>
        <p>Tanggal: {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Karyawan</th>
                <th>Waktu Masuk</th>
                <th>Waktu Keluar</th>
                <th>Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($absensis as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $item->karyawan->nama_lengkap ?? 'Unknown' }}</td>
                <td>{{ $item->waktu_masuk ? \Carbon\Carbon::parse($item->waktu_masuk)->format('H:i') : '-' }}</td>
                <td>{{ $item->waktu_keluar ? \Carbon\Carbon::parse($item->waktu_keluar)->format('H:i') : '-' }}</td>
                <td>
                    @php
                        $statusClass = 'status-Hadir';
                        if($item->status_kehadiran == 'Terlambat') $statusClass = 'status-Terlambat';
                        if($item->status_kehadiran == 'Lupa Checkout') $statusClass = 'status-Lupa';
                        if($item->status_kehadiran == 'Luar Kantor') $statusClass = 'status-Luar';
                        if($item->status_kehadiran == 'Tidak Hadir') $statusClass = 'status-Tidak';
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ $item->status_kehadiran }}
                    </span>
                </td>
                <td>{{ $item->catatan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">Tidak ada data absensi untuk tanggal ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
