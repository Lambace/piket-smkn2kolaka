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
                'auto_hadir', // ← BARU: dibutuhkan badge & toggle di tabel/form
            ])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|string|min:6',
            'role'               => 'nullable|in:petugas,koordinator', // ← BARU: hormati pilihan role dari form
            'jenis_kelamin'      => 'nullable|in:L,P',
            'nip'                => 'nullable|string|max:20',
            'golongan'           => 'nullable|string|max:10', // disamakan dengan update (XVII dll. aman)
            'status_kepegawaian' => 'nullable|in:PNS,PPPK Guru,PPPK/PW Guru,PPPK/Staf TU,PPPK/PW Staf TU,Guru Honorer',
            'hari_piket'         => 'nullable|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'auto_hadir'         => 'nullable|boolean', // ← BARU
        ]);

        User::create([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'password'           => bcrypt($validated['password']),
            'role'               => $validated['role'] ?? 'petugas', // ← BARU: tidak lagi dipaksa 'petugas'
            'jenis_kelamin'      => $validated['jenis_kelamin'] ?? null,
            'nip'                => $validated['nip'] ?? null,
            'golongan'           => $validated['golongan'] ?? null,
            'status_kepegawaian' => $validated['status_kepegawaian'] ?? null,
            'hari_piket'         => $validated['hari_piket'] ?? null,
            'auto_hadir'         => (bool) ($validated['auto_hadir'] ?? false), // ← BARU
        ]);

        return redirect()->route('user-petugas.index')
            ->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email,' . $user->id,
            'role'               => 'nullable|in:petugas,koordinator',
            'jenis_kelamin'      => 'nullable|in:L,P',
            'nip'                => 'nullable|string|max:20',
            'golongan'           => 'nullable|string|max:10',
            'status_kepegawaian' => 'nullable|in:PNS,PPPK Guru,PPPK/PW Guru,PPPK/Staf TU,PPPK/PW Staf TU,Guru Honorer', // disamakan dengan store
            'hari_piket'         => 'nullable|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'auto_hadir'         => 'nullable|boolean', // ← BARU
        ]);

        // ===== PROTEKSI: cegah sistem kehilangan koordinator terakhir =====
        $roleBaru = $validated['role'] ?? $user->role;
        if ($user->role === 'koordinator' && $roleBaru !== 'koordinator') {
            if (User::where('role', 'koordinator')->count() <= 1) {
                return back()->with('error', 'Role tidak dapat diturunkan: minimal harus ada 1 koordinator.');
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
            'hari_piket'         => $validated['hari_piket'] ?? null,
            'auto_hadir'         => (bool) ($validated['auto_hadir'] ?? false), // ← BARU
        ]);

        return redirect()->route('user-petugas.index')
            ->with('success', 'Data petugas berhasil diperbarui.');
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
        if ($user->role === 'koordinator' && User::where('role', 'koordinator')->count() <= 1) {
            return back()->with('error', 'Tidak bisa menghapus koordinator terakhir.');
        }

        $user->delete();
        return back()->with('success', 'Akun dihapus.');
    }
}