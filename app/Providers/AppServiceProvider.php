<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View as ViewFacade;
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
        Gate::before(function (User $user): ?bool {
            return $user->isAdmin() ? true : null;
        });

        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        ViewFacade::composer(['layouts.app', 'auth.login'], function (View $view): void {
            $logoPath = Setting::get('apotek_logo');

            $view->with([
                'apotekName' => Setting::get('apotek_name', (string) config('apotek.name', 'Apotek Billing')),
                'apotekLogoUrl' => $logoPath !== null && $logoPath !== '' ? route('settings.logo') : null,
            ]);
        });
    }
}
