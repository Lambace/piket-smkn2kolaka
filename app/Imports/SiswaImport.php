<?php

namespace App\Imports;

use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\ToArray;

class SiswaImport implements ToArray
{
    public int $imported = 0;
    public int $skipped = 0;

    public function array(array $rows)
    {
        if (count($rows) === 0) {
            return;
        }

        // Baris pertama = judul kolom
        $header = array_shift($rows);
        $header = array_map(
            fn ($h) => strtolower(str_replace([' ', '-'], '_', trim((string) $h))),
            $header
        );

        foreach ($rows as $row) {
            // Lewati baris kosong
            if (collect($row)->filter(fn ($v) => !blank($v))->isEmpty()) {
                continue;
            }

            $row = array_slice($row, 0, count($header));
            $row = array_pad($row, count($header), null);
            $data = array_combine($header, $row);

            $nisn = trim((string) ($data['nisn'] ?? ''));
            $nama = trim((string) ($data['nama'] ?? ''));

            if ($nisn === '' || $nama === '') {
                $this->skipped++;
                continue;
            }

            // NISN sudah ada → update, belum ada → buat baru
            Siswa::updateOrCreate(
                ['nisn' => $nisn],
                [
                    'nis' => trim((string) ($data['nis'] ?? '')),
                    'nama' => $nama,
                    'kelas' => trim((string) ($data['kelas'] ?? '')),
                    'jurusan' => trim((string) ($data['jurusan'] ?? '')) ?: null,
                    'jenis_kelamin' => trim((string) ($data['jenis_kelamin'] ?? '')) ?: null,
                    'alamat' => trim((string) ($data['alamat'] ?? '')) ?: null,
                    'telepon' => trim((string) ($data['telepon'] ?? '')) ?: null,
                ]
            );

            $this->imported++;
        }
    }
}