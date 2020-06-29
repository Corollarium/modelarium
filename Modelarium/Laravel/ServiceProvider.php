<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modelarium\Laravel\Console\Commands\ModelariumCommand::class,
                \Formularium\Laravel\Console\Commands\CommandDatatype::class,
                \Formularium\Laravel\Console\Commands\CommandValidator::class
            ]);
        }
    }
}
