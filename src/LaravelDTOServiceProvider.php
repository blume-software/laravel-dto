<?php

namespace Blume\LaravelDTO;

use Blume\LaravelDTO\OpenApi\Http\Controllers\OpenApiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelDTOServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/openapi.php', 'openapi');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-dto');

        if (config('openapi.enabled') !== true) {
            return;
        }

        Route::get('openapi', [OpenApiController::class, 'openapi'])->name('openapi');
        Route::get('openapi/ui', [OpenApiController::class, 'openapiUI'])->name('openapi.ui');
    }
}
