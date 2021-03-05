<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use Formularium\Factory\DatatypeFactory;
use Formularium\Factory\ValidatorFactory;
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
        /*
         * Commands
         */
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modelarium\Laravel\Console\Commands\ModelariumPublishCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumFrontendCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumScaffoldCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumDatatypeCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumRenderableCommand::class,
                \Modelarium\Laravel\Console\Commands\ModelariumTypeCommand::class,
            ]);
        }

        /*
         * Namespace registration
         */
        DatatypeFactory::appendNamespace('App\\Datatypes');
        ValidatorFactory::appendNamespace('App\\Validators');
        DatatypeFactory::registerFactory(
            'Modelarium\\Laravel\\Datatypes\\RelationshipFactory::factoryName'
        );

        /*
         * Publishing
         */
        $this->publishes([
            __DIR__ . '/Config/modelarium.php' => config_path('modelarium.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../Types/Graphql/directives.graphql' => base_path('graphql/modelariumdirectives.graphql'),
            __DIR__ . '/../Types/Graphql/scalars.graphql' => base_path('graphql/modelariumscalars.graphql'),
        ], 'schema');

        $this->publishes([
            __DIR__ . '/Graphql/user.graphql' => base_path('graphql/data/user.graphql'),
            __DIR__ . '/Graphql/schema.graphql' => base_path('graphql/schema.graphql'),
        ], 'schemabase');

        $vueStubs = [];
        $vueDir = __DIR__ . '/../Frontend/stubs/Vue/';
        foreach (scandir($vueDir) as $i) {
            if ($i == "." || $i == "..") {
                continue;
            }
            $vueStubs[$vueDir . $i] = base_path('resources/modelarium/stubs/Vue/' . $i);
        }
        $this->publishes($vueStubs, 'vue');

        /*
         * Events
         */
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
