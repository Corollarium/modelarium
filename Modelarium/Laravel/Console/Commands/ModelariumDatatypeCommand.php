<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Formularium\Factory\DatatypeFactory;
use Formularium\Exception\Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Console\Command;

use function Safe\substr;

class ModelariumDatatypeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:datatype
        {name : The datatype name}
        {--basetype=string : the basetype it inherits from ("string"), if there is one.}
        {--namespace=App\\Datatypes : the class namespace. Defaults to "App\\Datatypes"}
        {--path= : path to save the file. Defaults to base_path("app/Datatypes") }
        {--test-path= : path to save the file. Defaults to base_path("tests/Unit") }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates scaffolding using Modelarium';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        if (!is_string($name)) {
            $this->error('Name must be a string');
            return;
        }
        $ns = $this->option('namespace');
        if (!is_string($ns)) {
            $this->error('Namespace must be a string');
            return;
        }
        $path = $this->option('path') ?: base_path("app/Datatypes");

        if (!is_dir($path)) {
            \Safe\mkdir($path, 0777, true);
        }

        // call formularium
        $this->call('formularium:datatype', [
            'name' => $name,
            '--basetype' => $this->option('basetype'),
            '--namespace' => $ns,
            '--path' => $path,
            '--test-path' => $this->option('test-path') ?: base_path("tests/Unit"),
        ]);

        // create class
        $php = \Modelarium\Util::generateLighthouseTypeFile($name, $ns . '\\Types');
        $filename = $path . "/Types/Datatype_{$name}.php";
        if (!is_dir($path . "/Types")) {
            \Safe\mkdir($path . "/Types", 0777, true);
        }
        \Safe\file_put_contents($filename, $php);

        // regenerate graphql
        $datatypes = [];
        /** @var array<class-string> $classesInNamespace */
        $classesInNamespace = ClassFinder::getClassesInNamespace($ns . '\\Types');
        foreach ($classesInNamespace as $class) {
            $reflection = new \ReflectionClass($class);
            if (!$reflection->isInstantiable()) {
                continue;
            }

            $datatypes[$class] = substr($class, strpos($class, "Datatype_") + mb_strlen("Datatype_"));
        }
        $scalars = \Modelarium\Util::scalars(
            $datatypes,
            $ns . '\\Types'
        );
        \Safe\file_put_contents(base_path('graphql/types.graphql'), $scalars);

        $this->info('Remember to add `#import types.graphql` to your `graphql/schema.graphql` file.');
    }
}
