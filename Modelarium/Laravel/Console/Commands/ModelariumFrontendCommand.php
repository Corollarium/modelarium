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

        $this->info('Generating Frontend.');
      
        $composer = FrameworkComposer::create($frameworks);

        if ($name === '*' || $name === 'all') {
            // TODO: all classes
        } else {
            $model = $name::getFormularium();
            $generator = new FrontendGenerator($composer, $model);
            $collection = $generator->generate();
        }

        $this->writeFiles(
            $collection,
            base_path(),
            (bool)$this->option('overwrite')
        );
        $this->info('Finished frontend.');
    }
}
