<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Formularium\Factory\CodeGeneratorFactory;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Console\Command;
use Modelarium\Parser;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Laravel\Processor as LaravelProcessor;
use Modelarium\Options;

class ModelariumCodeCommand extends Command
{
    use WriterTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:code
        {name : The model name. Use "*" or "all" for all models}
        {--generator= : The generators to use [SQL, GraphQL, Typescript]}
        {--path= : The output directory. Defaults to `resources/{generator}/`}
        {--lighthouse : use lighthouse directives}
        {--overwrite : overwrite all files if they exist}
        {--prettier : run prettier on files}
        {--eslint : run eslint fix on files}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates code using Modelarium';

    /**
     * @var string CodeGenerator
     */
    protected $generatorName = "";

    /**
     * @var Parser
     */
    protected $parser = null;

    /**
     * @var Options
     */
    protected $modelariumOptions = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // read from Options()
        $this->modelariumOptions = new Options();
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
        $this->generatorName = $this->option('generator');
        if (empty($this->generatorName)) {
            $this->error('Which code format to generate. Example: `--generator=TypeScript`');
            return;
        }
        if (is_array($this->generatorName)) {
            $this->error('Please specify a single generator.');
            return;
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
            $this->generateFromModel('\\App\\Models\\' . $name);
        }
        $this->info('Finished frontend.');
    }

    protected function loadParser(): void
    {
        $files = [
            __DIR__ . '/../../../Types/Graphql/scalars.graphql'
        ];
        if ($this->option('lighthouse') || $this->modelariumOptions->getOption('modelarium', 'lighthouse')) {
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
        $generator = CodeGeneratorFactory::factory($this->generatorName);
        $model = $name::getFormularium();
        $this->info("Starting $name...");

        $code = $generator->type($model);

        if (!$code) {
            $this->info('Nothing generated.');
            return;
        }

        $basepath = $this->option('path') ?: base_path('resources/' . $generator->getName());
        if (!is_dir($basepath)) {
            \Safe\mkdir($basepath, 0777, true);
        }

        $collection = new GeneratedCollection();
        $collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $code,
                $generator->getFilename($model->getName())
            )
        );

        $collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $generator->datatypeDeclarations(),
                $generator->getFilename('Scalars')
            )
        );

        if (!$collection->count()) {
            $this->info('Nothing generated.');
            return;
        }

        $writtenFiles = $this->writeFiles(
            $collection,
            $basepath,
            function (GeneratedItem $i) {
                if ((bool)$this->option('overwrite') === true) {
                    return true;
                }
                return false;
            }
        );
        $this->info('Files generated.');

        if ($this->option('prettier') !== null ?: $this->modelariumOptions->getOption('frontend', 'prettier')) {
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

        if ($this->option('eslint') !== null ?: $this->modelariumOptions->getOption('frontend', 'eslint')) {
            $this->info('Running eslint on generated files.');
            $useYarn = file_exists(base_path('yarn.lock'));
            if ($useYarn) {
                $command = "cd $basepath && npx eslint --fix ";
            } else {
                $command = "cd $basepath && yarn eslint --fix ";
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
