<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Formularium\FrameworkComposer;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Console\Command;
use Modelarium\Parser;
use Modelarium\Frontend\FrontendGenerator;
use Modelarium\Laravel\Processor as LaravelProcessor;

class ModelariumFrontendCommand extends Command
{
    use WriterTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:frontend
        {name : The model name. Use "*" or "all" for all models}
        {--framework=* : The frameworks to use}
        {--lighthouse : use lighthouse directives}
        {--overwrite : overwrite files if they exist}
        {--prettier : run prettier on files}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates frontend using Modelarium';

    /**
     * @var string[] List of Frameworks to be passed to the FrameworkComposer
     */
    protected $frameworks;

    /**
     * @var Parser
     */
    protected $parser = null;

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

        // setup stuff
        // @phpstan-ignore-next-line
        $this->frameworks = $this->option('framework');
        if (empty($this->frameworks)) {
            $this->error('If you are generating frontend you need to specify frameworks. Example: `--framework=HTML --framework=Bootstrap --framework=Vue`');
            return;
        }
        if (!is_array($this->frameworks)) {
            // @phpstan-ignore-next-line
            $this->frameworks = [$this->frameworks];
        }
      
        $this->loadParser();
        if ($name === '*' || $name === 'all') {
            /** @var array<class-string> $classesInNamespace */
            $classesInNamespace = ClassFinder::getClassesInNamespace('App\\Models');

            foreach ($classesInNamespace as $class) {
                $reflection = new \ReflectionClass($class);
                if (!$reflection->isInstantiable()) {
                    continue;
                }
                $this->generateFromModel($class);
            }
            return;
        } elseif (is_array($name)) {
            // TODO
        } else {
            $this->generateFromModel($name);
        }
        $this->info('Finished frontend.');
    }

    protected function loadParser(): void
    {
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
        $this->parser = new Parser();
        $this->parser->setImport('directives.graphql', LaravelProcessor::getDirectivesGraphqlString());
        $this->parser->fromFiles($files);
    }

    protected function generateFromModel(string $name): void
    {
        $composer = FrameworkComposer::create($this->frameworks);
        $model = $name::getFormularium();

        $generator = new FrontendGenerator($composer, $model, $this->parser);
        $collection = $generator->generate();
    
        if (!$collection->count()) {
            $this->info('Nothing generated.');
            return;
        }

        $basepath = base_path('resources/js/components/');
        $writtenFiles = $this->writeFiles(
            $collection,
            $basepath,
            (bool)$this->option('overwrite')
        );
        $this->info('Files generated.');

        if ($this->option('prettier')) {
            $this->info('Running prettier on generated files.');
            $useYarn = file_exists(base_path('yarn.lock'));
            if ($useYarn) {
                $command = "cd $basepath && npx prettier --write ";
            } else {
                $command = "cd $basepath && yarn prettier --write ";
            }

            // this runs all prettier commands in parallel.
            $run = array_reduce(
                $writtenFiles,
                function ($carry, $f) use ($command) {
                    return $carry . '(' . $command . $f . ') & ';
                }
            );
            shell_exec($run . ' wait');
        }
    }
}
