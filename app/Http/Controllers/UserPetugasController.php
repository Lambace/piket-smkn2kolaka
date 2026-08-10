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
            'users' => User::select('id', 'name', 'email', 'role', 'created_at')
                ->orderByDesc('role')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(6)],
            'role'     => ['required', Rule::in(['koordinator', 'petugas'])],
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
        ]);

        return back()->with('success', 'Akun berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => ['required', Rule::in(['koordinator', 'petugas'])],
        ]);

        $user->update($data);

        return back()->with('success', 'Akun diperbarui.');
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