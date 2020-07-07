<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Formularium\FrameworkComposer;
use Formularium\Model;
use Illuminate\Console\Command;
use Modelarium\Frontend\FrontendGenerator;

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
        $frameworks = $this->option('framework');
        if (empty($frameworks)) {
            $this->error('If you are generating frontend you need to specify frameworks. Example: `--framework=HTML --framework=Bootstrap --framework=Vue`');
            return;
        }
        if (!is_array($frameworks)) {
            $frameworks = [$frameworks];
        }
      
        $composer = FrameworkComposer::create($frameworks);
        $collection = null;

        if ($name === '*' || $name === 'all') {
            // TODO: all classes
        } elseif (is_array($name)) {
            // TODO
        } else {
            $model = $name::getFormularium();
            $generator = new FrontendGenerator($composer, $model);
            $collection = $generator->generate();
        }

        if (!$collection) {
            $this->info('Nothing generated.');
            return;
        }

        $basepath = base_path();
        $writtenFiles = $this->writeFiles(
            $collection,
            $basepath,
            (bool)$this->option('overwrite')
        );

        if ($this->option('prettier')) {
            $this->info('Running prettier.');
            foreach ($writtenFiles as $f) {
                shell_exec("cd $basepath && npx prettier --write $f");
            }
        }
        $this->info('Finished frontend.');
    }
}
