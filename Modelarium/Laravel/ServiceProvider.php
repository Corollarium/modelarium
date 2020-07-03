<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Nuwave\Lighthouse\Events\RegisterDirectiveNamespaces;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modelarium\Laravel\Console\Commands\ModelariumCommand::class
            ]);
        }

        $this->publishes([
            __DIR__ . '/../Types/Graphql/directives.graphql' => base_path('graphql/modelarium.graphql'),
            __DIR__ . '/../Types/Graphql/scalars.graphql' => base_path('graphql/formularium.graphql'),
        ], 'schema');

        Event::listen(
            RegisterDirectiveNamespaces::class,
            function (RegisterDirectiveNamespaces $registerDirectiveNamespaces): string {
                return 'Modelarium\\Laravel\\Lighthouse\\Directives';
            }
        );
    }
}
