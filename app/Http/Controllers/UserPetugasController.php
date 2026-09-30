<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserPetugasController extends Controller
{
    public function index()
    {
        return Inertia::render('UserPetugas/Index', [
            'users' => User::select([
                'id', 'name', 'email', 'role',
                'jenis_kelamin', 'nip', 'golongan', 'status_kepegawaian',
                'hari_piket',
                'auto_hadir',
            ])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|string|min:6',
            'role'               => 'nullable|in:petugas,koordinator,wakasek', // ← wakasek ditambah
            'jenis_kelamin'      => 'nullable|in:L,P',
            'nip'                => 'nullable|string|max:20',
            'golongan'           => 'nullable|string|max:10',
            'status_kepegawaian' => 'nullable|in:PNS,PPPK Guru,PPPK/PW Guru,PPPK/Staf TU,PPPK/PW Staf TU,Guru Honorer',
            'hari_piket'         => 'nullable|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'auto_hadir'         => 'nullable|boolean',
        ]);

        // ===== LOGIKA KHUSUS WAKASEK =====
        // Wakasek tidak bertugas piket & tidak auto-hadir
        $roleBaru = $validated['role'] ?? 'petugas';
        $isWakasek = $roleBaru === 'wakasek';

        User::create([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'password'           => bcrypt($validated['password']),
            'role'               => $roleBaru,
            'jenis_kelamin'      => $validated['jenis_kelamin'] ?? null,
            'nip'                => $validated['nip'] ?? null,
            'golongan'           => $validated['golongan'] ?? null,
            'status_kepegawaian' => $validated['status_kepegawaian'] ?? null,
            'hari_piket'         => $isWakasek ? null : ($validated['hari_piket'] ?? null),
            'auto_hadir'         => $isWakasek ? false : (bool) ($validated['auto_hadir'] ?? false),
        ]);

        return redirect()->route('user-petugas.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email,' . $user->id,
            'role'               => 'nullable|in:petugas,koordinator,wakasek', // ← wakasek ditambah
            'jenis_kelamin'      => 'nullable|in:L,P',
            'nip'                => 'nullable|string|max:20',
            'golongan'           => 'nullable|string|max:10',
            'status_kepegawaian' => 'nullable|in:PNS,PPPK Guru,PPPK/PW Guru,PPPK/Staf TU,PPPK/PW Staf TU,Guru Honorer',
            'hari_piket'         => 'nullable|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'auto_hadir'         => 'nullable|boolean',
        ]);

        $roleBaru = $validated['role'] ?? $user->role;
        $isWakasek = $roleBaru === 'wakasek';

        // ===== PROTEKSI: cegah sistem kehilangan koordinator terakhir =====
        // Berlaku juga saat koordinator diturunkan menjadi petugas ATAU wakasek
        if ($user->role === 'koordinator' && $roleBaru !== 'koordinator') {
            if (User::where('role', 'koordinator')->count() <= 1) {
                return back()->with('error', 'Role tidak dapat diturunkan: minimal harus ada 1 koordinator aktif.');
            }
        }

        $user->update([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'role'               => $roleBaru,
            'jenis_kelamin'      => $validated['jenis_kelamin'] ?? null,
            'nip'                => $validated['nip'] ?? null,
            'golongan'           => $validated['golongan'] ?? null,
            'status_kepegawaian' => $validated['status_kepegawaian'] ?? null,
            'hari_piket'         => $isWakasek ? null : ($validated['hari_piket'] ?? null),
            'auto_hadir'         => $isWakasek ? false : (bool) ($validated['auto_hadir'] ?? false),
        ]);

        return redirect()->route('user-petugas.index')
            ->with('success', 'Data akun berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', Password::min(6)],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password berhasil direset.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }

        // ===== PROTEKSI: cegah menghapus koordinator terakhir =====
        // Wakasek & petugas boleh dihapus kapan saja tanpa batasan ini
        if ($user->role === 'koordinator' && User::where('role', 'koordinator')->count() <= 1) {
            return back()->with('error', 'Tidak bisa menghapus koordinator terakhir.');
        }

        $user->delete();
        return back()->with('success', 'Akun dihapus.');
    }
}