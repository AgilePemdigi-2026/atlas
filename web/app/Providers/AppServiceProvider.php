<?php

namespace App\Providers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
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
        View::composer(['partials.header', 'partials.footer', 'landing'], function (ViewContract $view): void {
            $menu = collect(['docs' => 'Docs', 'profile' => 'Profile', 'console' => 'Console'])
                ->map(fn (string $label, string $routeName): array => [
                    'label' => $label,
                    'href' => Route::has($routeName) ? route($routeName) : '#',
                ]);

            $view->with([
                'menu' => $menu,
                'loginHref' => Route::has('login') ? route('login') : '#',
            ]);
        });
    }
}
