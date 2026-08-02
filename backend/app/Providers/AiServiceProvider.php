<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Services\Ai\LocalRulesAiProvider;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            AiProvider::class,
            fn () => new LocalRulesAiProvider
        );
    }
}
