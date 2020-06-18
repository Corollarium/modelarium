<?php declare(strict_types=1);

namespace Modelarium\Laravel\ModelariumServiceProvider;

use Illuminate\Support\ServiceProvider;

class ModelariumServiceProvider extends ServiceProvider
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
                \Modelarium\Laravel\Console\Commands::class
            ]);
        }
    }
}
