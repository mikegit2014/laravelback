<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppContainerServiceProvider extends ServiceProvider
{
    /**
     * container list
     * @var array
     */
    private $containerList = [];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->containerList = require(__DIR__ . '/../../config/container.php');
        foreach ($this->containerList as $key => $value) {
            $this->app->singleton($key, function() use ($value) {
                return new $value();
            });

            $this->app->bind('force.' . $key, function() use ($value) {
                return new $value();
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}