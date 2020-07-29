<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Laravel\Processor as LaravelProcessor;
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

        $files = [
            __DIR__ . '/../../../Types/Graphql/scalars.graphql'
        ];

        if ($this->option('lighthouse')) {
            // TODO: see issue #2
            // generate lighthouse directives
            // $output = new BufferedOutput();
            // $this->call('lighthouse:ide-helper');
            // $output->fetch();

            $files[] = base_path('schema-directives.graphql');
        } else {
            $files[] = base_path(__DIR__ . '/../../Graphql/definitionsLighthouse.graphql');
        }

        if ($name === '*' || $name === 'all') {
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
