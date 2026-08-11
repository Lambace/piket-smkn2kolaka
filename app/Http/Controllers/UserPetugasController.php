<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
    ])->orderBy('name')->get(),
]);
    }

    public function store(Request $request)
{
   $validated = $request->validate([
    'name'               => 'required|string|max:255',
    'email'              => 'required|email|unique:users,email',
    'password'           => 'required|string|min:6',
    'jenis_kelamin'      => 'nullable|in:L,P',
    'nip'                => 'nullable|string|max:20',
    'golongan'           => 'nullable|string|max:5',
    'status_kepegawaian' => 'nullable|in:ASN,PPPK',
]);

User::create([
    'name'               => $validated['name'],
    'email'              => $validated['email'],
    'password'           => bcrypt($validated['password']),
    'role'               => 'petugas',
    'jenis_kelamin'      => $validated['jenis_kelamin'] ?? null,
    'nip'                => $validated['nip'] ?? null,
    'golongan'           => $validated['golongan'] ?? null,
    'status_kepegawaian' => $validated['status_kepegawaian'] ?? null,
]);

    return redirect()->route('user-petugas.index')
        ->with('success', 'Petugas berhasil ditambahkan.');
}
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
        'name'               => 'required|string|max:255',
        'email'              => 'required|email|unique:users,email,'.$user->id,
        'jenis_kelamin'      => 'nullable|in:L,P',
        'nip'                => 'nullable|string|max:20',
        'golongan'           => 'nullable|string|max:5',
        'status_kepegawaian' => 'nullable|in:ASN,PPPK',
    ]);

    $user->update([
        'name'               => $validated['name'],
        'email'              => $validated['email'],
        'jenis_kelamin'      => $validated['jenis_kelamin'] ?? null,
        'nip'                => $validated['nip'] ?? null,
        'golongan'           => $validated['golongan'] ?? null,
        'status_kepegawaian' => $validated['status_kepegawaian'] ?? null,
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
        // Cegah menghapus diri sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }
        $user->delete();
        return back()->with('success', 'Akun dihapus.');
    }
}