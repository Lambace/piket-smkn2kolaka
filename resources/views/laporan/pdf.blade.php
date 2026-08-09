<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Piket</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #4f46e5; padding-bottom: 15px; }
        .header h1 { margin: 0; font-size: 18px; color: #4f46e5; }
        .header h2 { margin: 5px 0; font-size: 14px; font-weight: normal; }
        .info { margin-bottom: 15px; padding: 10px; background: #f8fafc; border-left: 4px solid #4f46e5; }
        .info p { margin: 3px 0; }
        .summary { margin-bottom: 15px; }
        .summary-box { display: inline-block; padding: 8px 15px; margin: 3px; background: #e0e7ff; border-radius: 5px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
        th { background: #4f46e5; color: white; padding: 8px 5px; text-align: left; }
        td { padding: 6px 5px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .footer { margin-top: 40px; text-align: right; }
        .signature { margin-top: 50px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <h2>Laporan Piket - {{ ucfirst($jenis) }}</h2>
    </div>

    <div class="info">
        <p><strong>Periode:</strong> {{ $labelPeriode }}</p>
        <p><strong>Rentang:</strong> {{ $start->isoFormat('D MMMM Y') }} s/d {{ $end->isoFormat('D MMMM Y') }}</p>
        <p><strong>Total Record:</strong> {{ $data->count() }}</p>
        <p><strong>Tanggal Cetak:</strong> {{ $tanggalCetak->isoFormat('dddd, D MMMM Y [pukul] HH:mm') }}</p>
    </div>

    <div class="summary">
        @php
            $ringkasan = [
                'Keterlambatan' => $data->where('jenis_aktivitas', 'Keterlambatan')->count(),
                'Izin Keluar' => $data->where('jenis_aktivitas', 'Izin Keluar')->count(),
                'Pelanggaran' => $data->where('jenis_aktivitas', 'Pelanggaran')->count(),
                'Tamu' => $data->where('jenis_aktivitas', 'Tamu')->count(),
            ];
        @endphp
        @foreach($ringkasan as $label => $jumlah)
            <span class="summary-box">{{ $label }}: {{ $jumlah }}</span>
        @endforeach
    </div>

    @if($data->count() === 0)
        <p style="text-align: center; padding: 40px; color: #999;">Tidak ada data pada periode ini.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Jenis</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Detail</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            @if($row['jenis_aktivitas'] === 'Keterlambatan')
                                <span class="badge badge-red">Terlambat</span>
                            @elseif($row['jenis_aktivitas'] === 'Izin Keluar')
                                <span class="badge badge-yellow">Izin</span>
                            @elseif($row['jenis_aktivitas'] === 'Pelanggaran')
                                <span class="badge badge-blue">Pelanggaran</span>
                            @else
                                <span class="badge badge-green">Tamu</span>
                            @endif
                        </td>
                        <td>{{ $row['tanggal'] }}</td>
                        <td>{{ $row['siswa'] }}</td>
                        <td>{{ $row['kelas'] }}</td>
                        <td>{{ $row['detail'] }}</td>
                        <td>{{ $row['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <div class="signature">
            <p>Kolaka, {{ $tanggalCetak->isoFormat('D MMMM Y') }}</p>
            <p>Petugas Piket,</p>
            <br><br><br>
            <p><strong><u>( _______________________ )</u></strong></p>
            <p>NIP. _______________________</p>
        </div>
    </div>
</body>
</html>