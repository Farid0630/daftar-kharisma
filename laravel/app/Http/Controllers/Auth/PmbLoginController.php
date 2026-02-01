<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PmbLoginController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string'],   // dipakai sebagai identifier
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $identifier = trim($data['email']);
        $plainPassword = (string) $data['password'];
        $remember = (bool) ($data['remember'] ?? false);

        /**
         * 1) Login admin / user Laravel normal (tabel users)
         */
        // Jika email tersebut milik user admin, paksa remember=true agar sesi lebih lama
        $maybeUser = User::where('email', $identifier)->first();
        $forceRemember = false;
        if ($maybeUser && ($maybeUser->is_admin || ($maybeUser->role ?? '') === 'admin')) {
            $forceRemember = true;
        }

        $attemptRemember = $remember || $forceRemember;

        if (Auth::attempt(['email' => $identifier, 'password' => $plainPassword], $attemptRemember)) {
            $request->session()->regenerate();

            $redirect = Gate::allows('admin')
                ? route('admin.dashboard')
                : route('dashboard');

            return response()->json([
                'message'  => 'Login berhasil.',
                'redirect' => $redirect,
            ]);
        }

        /**
         * 2) Login dari tabel PMB (mandiri / kip / yayasan)
         */
        $pmbAccount = $this->findPmbAccount($identifier);

        if (!$pmbAccount) {
            throw ValidationException::withMessages([
                'email' => ['Email/username atau kata sandi salah.'],
            ]);
        }

        $hashOrPlain = (string) ($pmbAccount['password_hash'] ?? '');
        $ok = $this->looksHashed($hashOrPlain)
            ? Hash::check($plainPassword, $hashOrPlain)
            : hash_equals($hashOrPlain, $plainPassword);

        if (!$ok) {
            throw ValidationException::withMessages([
                'email' => ['Email/username atau kata sandi salah.'],
            ]);
        }

        // 3) Upsert ke users agar auth Laravel standar
        $emailForUsers = $pmbAccount['email'] ?: $identifier;

        $user = User::updateOrCreate(
            ['email' => $emailForUsers],
            [
                'name' => $pmbAccount['name'] ?: 'PMB User',
                'password' => $this->looksHashed($hashOrPlain) ? $hashOrPlain : Hash::make($hashOrPlain),
            ]
        );

        // Jika akun yang di-sync ke users memiliki flag admin, pastikan remember aktif
        $loginRemember = $remember || ($user->is_admin ?? false) || (($user->role ?? '') === 'admin');
        Auth::login($user, $loginRemember);
        $request->session()->regenerate();

        // ====== PENTING: session PMB yang dipakai semua tempat ======
        session([
            'pmb_source' => $pmbAccount['source'], // mandiri|kip|yayasan
            'pmb_id'     => $pmbAccount['id'],
        ]);

        return response()->json([
            'message'  => 'Login berhasil.',
            'redirect' => route('pmb.dashboard'),
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    private function findPmbAccount(string $identifier): ?array
    {
        $sources = [
            [
                'source' => 'mandiri',
                'table'  => 'pmb_registrations',
                'email'  => 'alamat_email',
                'user'   => null,
                'name'   => 'nama_lengkap',
                'pass'   => 'password',
            ],
            [
                'source' => 'kip',
                'table'  => 'pmb_kip_registrations',
                'email'  => 'alamat_email',
                'user'   => null,
                'name'   => 'nama_lengkap',
                'pass'   => 'kata_sandi_hash',
            ],
            [
                'source' => 'yayasan',
                'table'  => 'pmb_yayasan_registrations',
                'email'  => 'alamat_email',
                'user'   => 'username',
                'name'   => 'nama_lengkap',
                'pass'   => 'kata_sandi_hash',
            ],
        ];

        foreach ($sources as $s) {
            $q = DB::table($s['table'])->where($s['email'], $identifier);

            if (!empty($s['user'])) {
                $q->orWhere($s['user'], $identifier);
            }

            $row = $q->orderByDesc('id')->first();

            if ($row) {
                return [
                    'source'        => $s['source'],
                    'id'            => $row->id,
                    'email'         => $row->{$s['email']} ?? null,
                    'username'      => $s['user'] ? ($row->{$s['user']} ?? null) : null,
                    'name'          => $row->{$s['name']} ?? null,
                    'password_hash' => $row->{$s['pass']} ?? null,
                ];
            }
        }

        return null;
    }

    private function looksHashed(string $value): bool
    {
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$2a$')
            || str_starts_with($value, '$argon2');
    }
}
