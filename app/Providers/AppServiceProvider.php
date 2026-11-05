<?php

namespace App\Providers;

use App\Models\Sound;
use App\Policies\SoundPolicy;
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
     * Daftarkan semua policy di sini agar Gate mengetahuinya.
     */
    public function boot(): void
    {
        // Daftarkan SoundPolicy untuk model Sound
        // Penggunaan di controller: $this->authorize('update', $sound);
        // Penggunaan di view: @can('update', $sound)
        Gate::policy(Sound::class, SoundPolicy::class);
    }
}
