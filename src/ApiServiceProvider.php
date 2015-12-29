<?php

namespace Killtw\Api;

use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('api', function () {
            return $this->app->make(Api::class);
        });
    }
}
