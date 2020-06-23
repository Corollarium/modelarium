<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Modelarium\Laravel\Processor as LaravelProcessor;

// use Formularium\FrameworkComposer;
// use Formularium\Frontend\Blade\Framework as FrameworkBlade;
// use Formularium\Frontend\Vue\Framework as FrameworkVue;

class ModelariumCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:scaffold
        {name : The model name}
        {--framework=* : The frameworks to use}
        {--overwrite : overwrite files if they exist}
        {--all : make everything}
        {--model : make model}
        {--event : make event}
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
        if ($name === '*') {
            // @phpstan-ignore-next-line
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
                $data = file_get_contents($n);
                if ($data) {
                    $this->_handle($data);
                } else {
                    $this->error("Cannot open $n");
                }
            }
        } elseif (!$name || is_array($name)) {
            $this->error('Invalid name parameter');
            return;
        } else {
            $data = file_get_contents($this->getPathGraphql($name));
            if ($data) {
                $this->_handle($data);
            } else {
                $this->error("Cannot open model $name");
            }
        }
        $this->info('Finished. You might want to run `composer dump-autoload`');
    }

    protected function getPathGraphql(string $name): string
    {
        // @phpstan-ignore-next-line
        return base_path('graphql/' . $name . '.graphql');
    }

    protected function _handle(string $data): void
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

        // if ($this->hasOption('stubdir')) {
        //     $this->stubDir = $this->option('stubdir');
        // }

        $processor = new LaravelProcessor();

        // make stuff
        $processor->setRunModel($this->option('model') || $this->option('all'));
        $processor->setRunMigration($this->option('migration') || $this->option('all'));
        $processor->setRunFactory($this->option('factory') || $this->option('all'));
        $processor->setRunSeed($this->option('seed') || $this->option('all'));
        $processor->setRunPolicy($this->option('policy') || $this->option('all'));
        $processor->setRunEvent($this->option('event') || $this->option('all'));

        $data = $processor->processString($data);
        $data->writeFiles(base_path(), $this->option('overwrite'));

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
