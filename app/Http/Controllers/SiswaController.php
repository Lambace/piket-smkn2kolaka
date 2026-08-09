<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Exports\SiswaExport;
use App\Imports\SiswaImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Siswa::query();

        // Pencarian
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$request->search}%")
                ->orWhere('nisn', 'like', "%{$request->search}%")
                ->orWhere('kelas', 'like', "%{$request->search}%"));
        }

        // Filter
        if ($request->filled('kelas')) {
            $query->where('kelas', $request->kelas);
        }
        if ($request->filled('jurusan')) {
            $query->where('jurusan', $request->jurusan);
        }
        if ($request->filled('jenis_kelamin')) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        // Sorting
        $allowed = ['nisn', 'nama', 'kelas', 'jenis_kelamin'];
        $sort = in_array($request->input('sort', 'nama'), $allowed)
            ? $request->input('sort', 'nama')
            : 'nama';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $siswa = $query->orderBy($sort, $direction)->paginate(10)->withQueryString();

        // Daftar pilihan filter
        $filters = [
            'kelas' => Siswa::distinct()->orderBy('kelas')->pluck('kelas')->filter()->values(),
            'jurusan' => Siswa::distinct()->orderBy('jurusan')->pluck('jurusan')->filter()->values(),
        ];

        return Inertia::render('Siswa/Index', [
            'siswa' => $siswa,
            'filters' => $filters,
            'params' => $request->only(['search', 'kelas', 'jurusan', 'jenis_kelamin', 'sort', 'direction']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nisn' => 'required|string|max:20|unique:siswa,nisn',
            'nis' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('siswa', 'nis')->whereNotNull('nis'),
            ],
            'nama' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'jurusan' => 'nullable|string|max:100',
            'jenis_kelamin' => 'nullable|in:L,P',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
        ]);

        // Normalisasi NIS kosong jadi null
        if (empty(trim($data['nis'] ?? ''))) {
            $data['nis'] = null;
        }

        Siswa::create($data);

        return back()->with('success', 'Data siswa berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $siswa = Siswa::findOrFail($id);

        $data = $request->validate([
            'nisn' => 'required|string|max:20|unique:siswa,nisn,'.$id,
            'nis' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('siswa', 'nis')->ignore($id)->whereNotNull('nis'),
            ],
            'nama' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'jurusan' => 'nullable|string|max:100',
            'jenis_kelamin' => 'nullable|in:L,P',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
        ]);

        // Normalisasi NIS kosong jadi null
        if (empty(trim($data['nis'] ?? ''))) {
            $data['nis'] = null;
        }

        $siswa->update($data);

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Siswa::findOrFail($id)->delete();

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }

    public function export()
    {
        return Excel::download(new SiswaExport, 'data-siswa-'.now()->format('Ymd').'.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            // ===== FILE XLSX =====
            if ($extension === 'xlsx') {
                $import = new SiswaImport;
                DB::transaction(function () use ($import, $file) {
                    Excel::import($import, $file);
                });

                return back()->with('success', "Import selesai: {$import->imported} data tersimpan, {$import->skipped} baris dilewati.");
            }

            // ===== FILE CSV =====
            $path = $file->getRealPath();

            $sample = file_get_contents($path, false, null, 0, 1000);
            $delimiter = str_contains($sample, ';') ? ';' : ',';

            $handle = fopen($path, 'r');
            $header = fgetcsv($handle, null, $delimiter, '"', '\\');

            if (!$header) {
                fclose($handle);
                return back()->with('error', 'File CSV kosong.');
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

            $required = ['nisn', 'nis', 'nama', 'kelas'];
            if (array_diff($required, $header)) {
                fclose($handle);
                return back()->with('error', 'Format CSV tidak sesuai. Kolom wajib: '.implode(', ', $required).'. Gunakan tombol Export sebagai template.');
            }

            $rows = [];
            while (($row = fgetcsv($handle, null, $delimiter, '"', '\\')) !== false) {
                if ($row === [null] || $row === [] || $row === false) {
                    continue;
                }

                $row = array_slice($row, 0, count($header));
                $row = array_pad($row, count($header), null);
                $rows[] = array_combine($header, $row);
            }
            fclose($handle);

            $imported = 0;
            $skipped = 0;

            DB::transaction(function () use ($rows, &$imported, &$skipped) {
                foreach ($rows as $data) {
                    if (empty($data['nama']) || empty($data['nisn'])) {
                        $skipped++;
                        continue;
                    }

                    // Normalisasi NIS
                    $nis = trim($data['nis'] ?? '');
                    if ($nis === '' || $nis === '0') {
                        $nis = null;
                    }

                    Siswa::updateOrCreate(
                        ['nisn' => trim($data['nisn'])],
                        [
                            'nis' => $nis,
                            'nama' => trim($data['nama']),
                            'kelas' => trim($data['kelas'] ?? ''),
                            'jurusan' => trim($data['jurusan'] ?? '') ?: null,
                            'jenis_kelamin' => trim($data['jenis_kelamin'] ?? '') ?: null,
                            'alamat' => trim($data['alamat'] ?? '') ?: null,
                            'telepon' => trim($data['telepon'] ?? '') ?: null,
                        ]
                    );
                    $imported++;
                }
            });

            return back()->with('success', "Import selesai: {$imported} data tersimpan, {$skipped} baris dilewati.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal melakukan import: '.$e->getMessage());
        }
    }
}