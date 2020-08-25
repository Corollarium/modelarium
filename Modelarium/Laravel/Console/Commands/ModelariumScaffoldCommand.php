<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Laravel\Processor as LaravelProcessor;
use Modelarium\Laravel\Targets\ModelGenerator;
use Symfony\Component\Console\Output\BufferedOutput;

class ModelariumScaffoldCommand extends Command
{
    use WriterTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:scaffold
        {name : The model name. Use "*" or "all" for all models}
        {--framework=* : The frameworks to use}
        {--modelDir= : directory to create models. default: app/Models}
        {--overwrite : overwrite files if they exist}
        {--lighthouse : use lighthouse directives}
        {--everything : make everything}
        {--model : make model}
        {--event : make event}
        {--migration : make migration}
        {--factory : make factory}
        {--seed : make seed}
        {--policy : make policy}';

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

        if ($this->option('modelDir')) {
            ModelGenerator::setModelDir($this->option('modelDir'));
        }

        $files = [
            __DIR__ . '/../../../Types/Graphql/scalars.graphql'
        ];

        if ($this->option('lighthouse')) {
            $files[] = __DIR__ . '/../../Graphql/definitionsLighthouse.graphql';
        }

        $path = base_path('graphql');
        $dir = \Safe\scandir($path);

        // parse directives from lighthouse
        $modelNames = array_diff($dir, array('.', '..'));
        
        foreach ($modelNames as $n) {
            if (mb_strpos($n, '.graphql') === false) {
                continue;
            }
            $files[] = base_path('graphql/' . $n);
        }
        $processor->processFiles($files);

        $files = $processor->getCollection();
        if ($name && $name !== '*' && $name !== 'all') {
            $files = $files->filter(
                function (GeneratedItem $g) use ($name) {
                    if (is_array($name)) {
                        throw new \Exception('Arrays not supported yet');
                    } else {
                        return mb_stripos($g->filename, $name);
                    }
                }
            );
        }

        $this->writeFiles(
            $files,
            base_path(),
            (bool)$this->option('overwrite')
        );
        $this->info('Finished scaffolding. You might want to run `composer dump-autoload`');
    }

    protected function getPathGraphql(string $name): string
    {
        return base_path('graphql/' . $name . '.graphql');
    }
}
