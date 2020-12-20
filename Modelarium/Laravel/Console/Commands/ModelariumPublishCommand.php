<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Modelarium\Modelarium;

use function Safe\unlink;

class ModelariumPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes project files from Modelarium';

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
        $this->info("Removing original User code.");
        $f = base_path('app/User.php');
        if (file_exists($f)) {
            unlink($f);
        }
        $f = base_path('database/migrations/2014_10_12_000000_create_users_table.php');
        if (file_exists($f)) {
            unlink($f);
        }

        foreach (Modelarium::getDirectiveLaravelLibraries() as $plugin) {
            $this->call('vendor:publish', [
                '--provider' => "$plugin\\Laravel\\ServiceProvider",
                '--tag' => "schema",
                '--force' => true
            ]);
    
            $this->call('vendor:publish', [
                '--provider' => "$plugin\\Laravel\\ServiceProvider",
                '--tag' => "schemabase",
            ]);
        }

        $this->info("Setup done.");
    }
}
