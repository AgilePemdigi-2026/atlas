<?php

namespace App\Providers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
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
            $routes = ['docs' => 'Docs'];

            if (Auth::check()) {
                $routes['profile'] = 'Profile';
                $routes['console'] = 'Console';
            }

            $menu = collect($routes)
                ->map(fn (string $label, string $routeName): array => [
                    'label' => $label,
                    'href' => Route::has($routeName) ? route($routeName) : '#',
                ]);

            $view->with('menu', $menu);
        });
    }
}
