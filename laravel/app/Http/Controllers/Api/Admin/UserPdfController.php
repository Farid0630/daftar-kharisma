<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;

// Sesuaikan model Anda:
use App\Models\User;
use App\Models\PmbRegistration;        // Mandiri (contoh)
use App\Models\PmbKipRegistration;     // KIP (contoh)
use App\Models\PmbYayasanRegistration; // Yayasan (contoh)

class UserPdfController extends Controller
{
    public function download(Request $request, string $id)
    {
        $user = User::query()->findOrFail($id);

        $email = (string) ($user->email ?? '');
        $email = trim($email);

        // Ambil data dari 3 tabel PMB berdasarkan email user
        $pmbMandiri = $email ? $this->findByEmailSafe(PmbRegistration::query(), $email)->latest()->first() : null;
        $pmbKip     = $email ? $this->findByEmailSafe(PmbKipRegistration::query(), $email)->latest()->first() : null;
        $pmbYayasan = $email ? $this->findByEmailSafe(PmbYayasanRegistration::query(), $email)->latest()->first() : null;

        // Render PDF
        $pdf = Pdf::loadView('admin.pdf.user', [
            'user'       => $user,
            'pmbMandiri' => $pmbMandiri,
            'pmbKip'     => $pmbKip,
            'pmbYayasan' => $pmbYayasan,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("user-{$user->id}.pdf");
    }

    /**
     * Query builder yang aman:
     * - hanya menambah kondisi kalau kolomnya memang ada di tabel
     * - menghindari error "Unknown column"
     */
    private function findByEmailSafe($query, string $email)
    {
        $model = $query->getModel();
        $table = $model->getTable();

        // Beberapa kemungkinan kolom email di tabel PMB Anda:
        $candidates = ['alamat_email', 'email'];

        $query->where(function ($q) use ($table, $email, $candidates) {
            $hasAny = false;

            foreach ($candidates as $col) {
                if (Schema::hasColumn($table, $col)) {
                    if (!$hasAny) {
                        $q->where($col, $email);
                        $hasAny = true;
                    } else {
                        $q->orWhere($col, $email);
                    }
                }
            }

            // Jika tidak ada kolom yang cocok, paksa hasil kosong (agar tidak return semua data)
            if (!$hasAny) {
                $q->whereRaw('1=0');
            }
        });

        return $query;
    }
}
