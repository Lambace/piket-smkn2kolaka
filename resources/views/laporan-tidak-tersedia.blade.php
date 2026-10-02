<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? 'Data Tidak Tersedia' }} — Sistem Informasi Piket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-xl">

        {{-- Header merah --}}
        <div class="bg-red-600 px-8 py-6">
            <h1 class="flex items-center gap-3 text-2xl font-extrabold text-white">
                <span>⚠️</span> {{ $judul ?? 'Daftar Hadir Tidak Tersedia' }}
            </h1>
        </div>

        <div class="space-y-6 bg-slate-50 p-8">

            {{-- Pesan utama --}}
            <div class="rounded-r-lg border-l-4 border-red-600 bg-red-50 px-5 py-4 text-slate-700">
                {{ $pesan }}
            </div>

            {{-- Langkah --}}
            <div>
                <h2 class="mb-3 font-bold text-slate-800">Langkah yang dapat dilakukan:</h2>
                <ol class="list-decimal space-y-2 pl-6 text-slate-700">
                    <li>Ini bukan kesalahan sistem — memang tidak ada petugas yang dijadwalkan piket untuk kombinasi tanggal/filter yang Anda pilih.</li>
                    <li>Periksa penugasan hari piket pada menu User Petugas, pastikan hari yang dipilih memiliki petugas.</li>
                    <li>Ubah tanggal atau filter hari pada halaman laporan untuk melihat hari lain yang memiliki jadwal piket.</li>
                </ol>
            </div>

            {{-- Detail teknis (collapsible) --}}
            <details class="rounded-lg border border-slate-200 bg-slate-100 px-5 py-3">
                <summary class="cursor-pointer font-semibold text-slate-700">
                    🔧 Detail Teknis (khusus administrator)
                </summary>
                <div class="mt-3 space-y-1 font-mono text-xs text-slate-600">
                    <p>Route&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ request()->path() }}</p>
                    <p>Query&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ http_build_query(request()->query()) ?: '(kosong)' }}</p>
                    <p>Hari target&nbsp;&nbsp;: {{ $hariTarget ?? '-' }}</p>
                    <p>Waktu&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ now('Asia/Makassar')->format('d-m-Y H:i') }} WITA</p>
                </div>
            </details>

            {{-- Tombol aksi --}}
            <div class="flex gap-3">
                <button
                    onclick="history.back()"
                    class="rounded-lg bg-blue-600 px-6 py-3 font-bold text-white transition hover:bg-blue-700"
                >← Kembali</button>
                <button
                    onclick="location.reload()"
                    class="rounded-lg bg-slate-200 px-6 py-3 font-bold text-slate-700 transition hover:bg-slate-300"
                >🔄 Coba Lagi</button>
            </div>
        </div>
    </div>
</body>
</html>