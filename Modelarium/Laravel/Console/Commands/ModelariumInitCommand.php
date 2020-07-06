<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Illuminate\Console\Command;

class ModelariumInitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inits project for Modelarium';

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

        $this->call('vendor:publish', [
            '--provider' => "Modelarium\\Laravel\\ServiceProvider",
            '--tag' => "schema"
        ]);

        $this->info("Setup done.");
    }
}
