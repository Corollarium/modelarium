<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Formularium\Factory\DatatypeFactory;
use Formularium\Exception\Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Console\Command;

use function Safe\substr;

class ModelariumRenderableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:renderable
        {name : The datatype name}
        {--framework=* : The frameworks to use. You can use this options several times. Use "*" for all.}
        {--namespace=App\\Frontend : path to save the file. Defaults to "App\\Frontend" }
        {--path= : base path of the namespace. Defaults to base_path("app/Frontend") }
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
        $path = $this->option('path') ?: base_path("app/Frontend");

        if (!is_dir($path)) {
            \Safe\mkdir($path, 0777, true);
        }

        // call formularium
        $formulariumCall = $this->call('formularium:renderable', [
            'name' => $name,
            '--framework' => $this->option('framework'),
            '--namespace' => $ns,
            '--path' => $path,
        ]);
        if ($formulariumCall) {
            $this->error('Error calling formularium:renderable');
            return;
        }

        $this->info('You might want to run composer dump-autoload');
    }
}
