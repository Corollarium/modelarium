<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use Formularium\Formularium;
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
                \Modelarium\Laravel\Console\Commands\ModelariumInitCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumScaffoldCommand::class,
            ]);
        }

        Formularium::appendDatatypeNamespace('App\\Datatypes');
        Formularium::appendValidatorNamespace('App\\Validators');

        $this->publishes([
            __DIR__ . '/../Types/Graphql/directives.graphql' => base_path('graphql/modelarium.graphql'),
            __DIR__ . '/../Types/Graphql/scalars.graphql' => base_path('graphql/formularium.graphql'),
            __DIR__ . '/Graphql/user.graphql' => base_path('graphql/data/user.graphql'),
            __DIR__ . '/Graphql/schema.graphql' => base_path('graphql/schema.graphql'),
        ], 'schema');

        Event::listen(
            RegisterDirectiveNamespaces::class,
            function (RegisterDirectiveNamespaces $registerDirectiveNamespaces): string {
                return 'Modelarium\\Laravel\\Lighthouse\\Directives';
            }
        );
    }
}
