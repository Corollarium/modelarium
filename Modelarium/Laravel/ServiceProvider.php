<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use Formularium\Factory\DatatypeFactory;
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
                \Modelarium\Laravel\Console\Commands\ModelariumFrontendCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumScaffoldCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumDatatypeCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumTypeCommand::class,
            ]);
        }

        Formularium::appendDatatypeNamespace('App\\Datatypes');
        Formularium::appendValidatorNamespace('App\\Validators');
        DatatypeFactory::registerFactory(
            'Modelarium\\Laravel\\Datatypes\\Datatype_relationship::factoryName'
        );

        $this->publishes([
            __DIR__ . '/../Types/Graphql/directives.graphql' => base_path('graphql/modelariumdirectives.graphql'),
            __DIR__ . '/../Types/Graphql/scalars.graphql' => base_path('graphql/modelariumscalars.graphql'),
        ], 'schema');

        $this->publishes([
            __DIR__ . '/Graphql/user.graphql' => base_path('graphql/data/user.graphql'),
            __DIR__ . '/Graphql/schema.graphql' => base_path('graphql/schema.graphql'),
        ], 'schemabase');

        Event::listen(
            RegisterDirectiveNamespaces::class,
            function (RegisterDirectiveNamespaces $registerDirectiveNamespaces): string {
                return 'Modelarium\\Laravel\\Lighthouse\\Directives';
            }
        );
        Event::listen(
            RegisterDirectiveNamespaces::class,
            function (RegisterDirectiveNamespaces $registerDirectiveNamespaces): string {
                return 'App\\Datatypes\\Types';
            }
        );
    }
}
