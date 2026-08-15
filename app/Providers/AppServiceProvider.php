<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ServiceOrder;
use App\Observers\ProductObserver;
use App\Observers\ServiceOrderObserver;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

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
        ServiceOrder::observe(ServiceOrderObserver::class);
        Product::observe(ProductObserver::class);

        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->registerInertiaErrorPages();
    }

    /**
     * Register rate limiters for sensitive endpoints.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('tracking-lookups', fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('pdf-downloads', fn (Request $request): Limit => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Render Inertia pages for HTTP error responses.
     */
    protected function registerInertiaErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            if (! in_array($response->statusCode(), [403, 404, 419, 429, 500, 503])) {
                return null;
            }

            return $response->render('error', [
                'status' => $response->statusCode(),
            ])->withSharedData();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
