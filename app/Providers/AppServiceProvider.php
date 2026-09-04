<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\Strike;
use App\Models\WorkCenterBalance;
use App\Observers\PanelVersionObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);
        
        // Gates de autorización
        Gate::define('isAdmin', function ($user) {
            return $user->id_profile === 1;
        });
        
        Gate::define('isSupervisor', function ($user) {
            return $user->id_profile === 5;
        });

        // Sello de versión que usan los paneles de TV para refrescarse al instante
        foreach ([Schedule::class, Strike::class, DailyProgram::class, Attribute::class, WorkCenterBalance::class] as $model) {
            $model::observe(PanelVersionObserver::class);
        }
    }
}
