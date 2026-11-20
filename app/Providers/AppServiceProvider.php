<?php

namespace App\Providers;

use App\Models\PengajuanCuti;
use App\Models\PengajuanHeader;
use App\Models\Task;
use App\Policies\CutiPolicy;
use App\Policies\PengajuanPolicy;
use App\Policies\TaskPolicy;
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

        Gate::policy(PengajuanCuti::class, CutiPolicy::class);
        Gate::policy(PengajuanHeader::class, PengajuanPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
