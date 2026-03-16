<?php

namespace App\Providers;

use App\Models\Base;
use App\Models\Colorway;
use App\Models\Media;
use App\Observers\BaseObserver;
use App\Observers\ColorwayObserver;
use App\Observers\MediaObserver;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Sentry\Laravel\Integration;
use Sentry\State\Scope;

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

        Event::listen(Authenticated::class, function (Authenticated $event): void {
            $user = $event->user;
            Integration::configureScope(static function (Scope $scope) use ($user): void {
                $scope->setUser([
                    'id' => (string) $user->getAuthIdentifier(),
                    'name' => $user->name ?? null,
                    'email' => $user->email ?? null,
                ]);
            });
        });
    }
}
