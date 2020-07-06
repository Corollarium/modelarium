<?php declare(strict_types=1);

namespace Formularium\Laravel\Commands;

use Formularium\DatatypeFactory;
use Formularium\Exception\Exception;
use Illuminate\Console\Command;

class ModelariumDatatypeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:datatype
        {name : The datatype name}
        {--basetype= : the basetype it inherits from ("string"), if there is one.}
        {--namespace= : the class namespace. Defaults to "\\App\\Datatypes"}
        {--path= : path to save the file. Defaults to "basepath("app\\Datatypes") }
        {--test-path= : path to save the file. Defaults to "basepath("tests/Unit") }
    ';

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
        // call formularium
        $this->call('formularium:datatype', [
            'name' => $this->argument('name'),
            '--basetype' => $this->argument('basetype'),
            '--namespace' => $this->argument('namespace'),
            '--path' => $this->argument('path'),
            '--test-path' => $this->argument('test-path'),
        ]);

        // regenerate graphql
        // TODO \Modelarium\Util::scalars()

        // LaravelProcessor::getDirectivesGraphqlString(
        //     [ 'Modelarium\\Laravel\\Lighthouse\\Directives' ,
        //     'App\\Directives'
        //     ]
        // );
    }
}
