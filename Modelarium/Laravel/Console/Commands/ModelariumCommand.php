<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use App\Console\Commands\base_path;
use Illuminate\Console\Command;
use Modelarium\Laravel\Targets\FactoryGenerator;
use Modelarium\Laravel\Targets\SeedGenerator;

// use Formularium\FrameworkComposer;
// use Formularium\Frontend\Blade\Framework as FrameworkBlade;
// use Formularium\Frontend\Vue\Framework as FrameworkVue;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Parser;

class ModelariumCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formularium:scaffold
        {name : The model name}
        {--framework=* : The frameworks to use}
        {--overwrite : overwrite files if they exist}
        {--all : make everything}
        {--model : make model}
        {--controller : make controller}
        {--migration : make migration}
        {--factory : make factory}
        {--seed : make seed}
        {--policy : make policy}
        {--frontend : make frontend files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates scaffolding using Formularium';

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
        if ($name === '*') {
            $path = base_path('graphql');
            $dir = scandir($path);
            if ($dir === false) {
                $this->error("Cannot find model dir $path");
                return 1;
            }

            $modelNames = array_diff($dir, array('.', '..'));
            foreach ($modelNames as $n) {
                if (mb_strpos($n, '.php') === false) {
                    continue;
                }
                $this->_handle(str_replace('.php', '', $n));
            }
        } else {
            $this->_handle($name);
        }
        $this->info('Finished. You might want to run `composer dump-autoload`');
    }

    protected function _handle(string $name): void
    {
        // TODO
        // // setup stuff
        // $frameworks = $this->option('framework');
        // FrameworkComposer::set($frameworks);

        // /**
        //  * @var FrameworkVue $vue
        //  */
        // $vue = FrameworkComposer::getByName('Vue');
        // $blade = FrameworkComposer::getByName('Blade');

        if ($this->hasOption('stubdir')) {
            $this->stubDir = $this->option('stubdir');
        }

        $parser = Parser::fromPath(base_path($name . '.graphql'));

        // make stuff
        if ($this->option('model') || $this->option('all')) {
        }
        if ($this->option('migration') || $this->option('all')) {
        }
        if ($this->option('factory') || $this->option('all')) {
        }
        if ($this->option('seed') || $this->option('all')) {
        }
        if ($this->option('policy') || $this->option('all')) {
            //     $this->makePolicy();
        }
        // if ($this->option('frontend') || $this->option('all')) {
        //     if ($vue) {
        //         $this->makeVueScaffold();
        //         $this->makeVue($vue, 'Base', 'viewable');
        //         $this->makeVue($vue, 'Item', 'viewable');
        //         $this->makeVue($vue, 'ListPage', 'viewable');
        //         $this->makeVue($vue, 'ShowPage', 'viewable');
        //         $this->makeVue($vue, 'EditPage', 'editable');
        //         $this->line('Generated Vue');
        //     } elseif ($blade) {
        //         $this->makeBlade($blade, 'show', 'viewable');
        //         $this->makeBlade($blade, 'index', 'viewable');
        //         $this->makeBlade($blade, 'form', 'editable');
        //         $this->line('Generated Blade');
        //     } else {
        //         // TODO: react?
        //     }
        // }
    }
}
