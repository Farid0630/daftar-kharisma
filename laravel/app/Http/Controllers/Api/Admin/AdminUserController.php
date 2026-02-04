<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function merged()
    {
        // 1) users table
        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'is_admin',
                'created_at',
            ])
            ->get()
            ->map(function ($u) {
                return [
                    'type' => 'user',
                    'source' => null,
                    'id' => (string) $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'is_admin' => (int) $u->is_admin,
                    'created_at' => $u->created_at,
                ];
            });

        // 2) pmb mandiri
        $mandiri = DB::table('pmb_registrations')
            ->select(['id', 'nama_lengkap', 'alamat_email'])
            ->get()
            ->map(fn ($r) => [
                'type' => 'pmb',
                'source' => 'mandiri',
                'id' => (string) $r->id,
                'name' => $r->nama_lengkap,
                'email' => $r->alamat_email,
            ]);

        // 3) pmb kip
        $kip = DB::table('pmb_kip_registrations')
            ->select(['id', 'nama_lengkap', 'alamat_email'])
            ->get()
            ->map(fn ($r) => [
                'type' => 'pmb',
                'source' => 'kip',
                'id' => (string) $r->id,
                'name' => $r->nama_lengkap,
                'email' => $r->alamat_email,
            ]);

        // 4) pmb yayasan
        $yayasan = DB::table('pmb_yayasan_registrations')
            ->select(['id', 'nama_lengkap', 'alamat_email'])
            ->get()
            ->map(fn ($r) => [
                'type' => 'pmb',
                'source' => 'yayasan',
                'id' => (string) $r->id,
                'name' => $r->nama_lengkap,
                'email' => $r->alamat_email,
            ]);

        $all = $users
            ->concat($mandiri)
            ->concat($kip)
            ->concat($yayasan)
            ->values();

        return response()->json(['data' => $all]);
    }

    public function show(User $user)
    {
        return response()->json(['data' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['nullable', 'string', 'max:255'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        // Aman: hanya set is_admin jika field dikirim
        if ($request->has('is_admin')) {
            $data['is_admin'] = (int) $request->boolean('is_admin');
        } else {
            unset($data['is_admin']);
        }

        $user->fill($data);
        $user->save();

        return response()->json([
            'message' => 'User updated',
            'data' => $user,
        ]);
    }

    public function destroy(Request $request, User $user)
{
    // Proteksi: jangan hapus diri sendiri
    if (Auth::id() === $user->id) {
        return response()->json(['message' => 'Tidak boleh menghapus akun sendiri.'], 422);
    }

    // Hard delete jika pakai SoftDeletes, kalau tidak ya delete biasa
    if (method_exists($user, 'forceDelete')) {
        $user->forceDelete();
    } else {
        $user->delete();
    }

    return response()->json(['message' => 'User berhasil dihapus.']);
}

}
