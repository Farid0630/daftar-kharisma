<?php

namespace App\Providers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Gate::define('admin', function ($user) {
            // Sesuaikan dengan struktur kolom user Anda:
            // - role = 'admin'  (string)
            // - atau is_admin = 1 (boolean/int)
            return ($user->role ?? null) === 'admin'
                || (bool) ($user->is_admin ?? false);
        });
    }
}
