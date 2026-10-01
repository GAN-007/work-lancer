<?php

namespace App\Providers;

use App\Galika\Contracts\BrowserExecutionProvider;
use App\Galika\Services\OpenWebAgentAdapter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(BrowserExecutionProvider::class, OpenWebAgentAdapter::class);
    }

    public function boot()
    {
        //
    }
}
