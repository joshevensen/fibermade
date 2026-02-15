<?php

namespace App\Providers;

use App\Models\Base;
use App\Models\Colorway;
use App\Models\Media;
use App\Observers\BaseObserver;
use App\Observers\ColorwayObserver;
use App\Observers\MediaObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        Colorway::observe(ColorwayObserver::class);
        Base::observe(BaseObserver::class);
        Media::observe(MediaObserver::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?? $request->ip());
        });
    }
}
