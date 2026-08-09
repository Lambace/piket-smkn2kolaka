<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $data;
    protected $labelPeriode;
    protected $jenis;

    public function __construct($data, $labelPeriode, $jenis)
    {
        $this->data = $data;
        $this->labelPeriode = $labelPeriode;
        $this->jenis = $jenis;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Jenis Aktivitas',
            'Tanggal',
            'Jam',
            'Nama',
            'Kelas/Instansi',
            'NISN/Telepon',
            'Detail',
            'Keterangan',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row['jenis_aktivitas'],
            $row['tanggal'],
            $row['jam'],
            $row['siswa'],
            $row['kelas'],
            $row['nisn'],
            $row['detail'],
            $row['keterangan'],
            $row['status'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header info sekolah
        $sheet->setCellValue('A1', config('app.name'));
        $sheet->setCellValue('A2', 'Laporan Piket - ' . ucfirst($this->jenis));
        $sheet->setCellValue('A3', 'Periode: ' . $this->labelPeriode);
        $sheet->setCellValue('A4', 'Dicetak: ' . now()->isoFormat('D MMMM Y, HH:mm'));

        // Style header info
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true);
        $sheet->getStyle('A4')->getFont()->setItalic(true)->getColor()->setARGB('FF666666');

        // Geser header tabel ke baris 6
        $sheet->getStyle('A6:I6')->getFont()->setBold(true);
        $sheet->getStyle('A6:I6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle('A6:I6')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Border tabel
        $lastRow = $this->data->count() + 6;
        $sheet->getStyle('A6:I' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Merge header info
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');
        $sheet->mergeCells('A4:I4');

        return [];
    }
}