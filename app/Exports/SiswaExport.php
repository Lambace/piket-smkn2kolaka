<?php

namespace App\Exports;

use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SiswaExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public function collection()
    {
        return Siswa::orderBy('kelas')->orderBy('nama')->get();
    }

    public function headings(): array
    {
        return ['NISN', 'NIS', 'Nama', 'Kelas', 'Jurusan', 'Jenis Kelamin', 'Alamat', 'Telepon'];
    }

    public function map($siswa): array
    {
        return [
            $siswa->nisn,
            $siswa->nis,
            $siswa->nama,
            $siswa->kelas,
            $siswa->jurusan ?? '',
            $siswa->jenis_kelamin ?? '',
            $siswa->alamat ?? '',
            $siswa->telepon ?? '',
        ];
    }

    public function title(): string
    {
        return 'Data Siswa';
    }
}