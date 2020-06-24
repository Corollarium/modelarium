<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Modelarium\GeneratedCollection;
use Modelarium\Laravel\Processor as LaravelProcessor;
use Symfony\Component\Console\Output\BufferedOutput;

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
        {--everything : make everything}
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

        $processor = new LaravelProcessor();

        // parse args
        $processor->setRunModel($this->option('model') || $this->option('everything'));
        $processor->setRunMigration($this->option('migration') || $this->option('everything'));
        $processor->setRunFactory($this->option('factory') || $this->option('everything'));
        $processor->setRunSeed($this->option('seed') || $this->option('everything'));
        $processor->setRunPolicy($this->option('policy') || $this->option('everything'));
        $processor->setRunEvent($this->option('event') || $this->option('everything'));

        // TODO: see issue #2
        // generate lighthouse directives
        // $output = new BufferedOutput();
        // $this->call('lighthouse:ide-helper');
        // $output->fetch();

        if ($name === '*' || $name === 'all') {
            // @phpstan-ignore-next-line
            $path = base_path('graphql');
            $dir = \Safe\scandir($path);

            // parse directives from lighthouse
            $modelNames = array_diff($dir, array('.', '..'));
            $files = [
                base_path('schema-directives.graphql'),
                __DIR__ . '/../../Graphql/definitions.graphql'
            ];
            
            foreach ($modelNames as $n) {
                if (mb_strpos($n, '.graphql') === false) {
                    continue;
                }
                // @phpstan-ignore-next-line
                $files[] = base_path('graphql/' . $n);
            }
            $processor->processFiles($files);
        } elseif (!$name || is_array($name)) {
            $this->error('Invalid name parameter');
            return;
        } else {
            try {
                $data = \Safe\file_get_contents($this->getPathGraphql($name));
                $processor->processString($data);
            } catch (\Safe\Exceptions\FilesystemException $e) {
                $this->error("Cannot open model $name");
            }
        }

        $this->writeFiles(
            $processor->getCollection(),
            // @phpstan-ignore-next-line
            base_path(),
            (bool)$this->option('overwrite')
        );

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



        // if ($this->option('frontend') || $this->option('everything')) {
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

    public function writeFiles(GeneratedCollection $collection, string $basepath, bool $overwrite = true): self
    {
        foreach ($collection as $element) {
            /**
             * @var GeneratedItem $element
             */
            $path = $basepath . '/' . $element->filename;
            $this->writeFile(
                $path,
                ($element->onlyIfNewFile ? false : $overwrite),
                $element->contents
            );
        }
        return $this;
    }

    /**
     * Takes a stub file and generates the target file with replacements.
     *
     * @param string $targetPath The path for the stub file.
     * @param boolean $overwrite
     * @param string $data The data to write
     * @return void
     */
    protected function writeFile(string $targetPath, bool $overwrite, string $data)
    {
        if (file_exists($targetPath) && !$overwrite) {
            $this->comment("File $targetPath already exists, not overwriting.");
            return;
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            \Safe\mkdir($dir, 0777, true);
        }

        $ret = \Safe\file_put_contents($targetPath, $data);
        if (!$ret) {
            $this->error("Cannot write to $targetPath");
            return;
        }
        $this->line("Wrote $targetPath");
    }
}
