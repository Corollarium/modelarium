<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Modelarium\GeneratorNameTrait;

class ModelariumTypeCommand extends Command
{
    use WriterTrait;
    use GeneratorNameTrait;

    /**
     * @var string
     */
    protected $stubDir = '.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:type
        {name : The model name.}
        {--overwrite : overwrite files if they exist}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a Graphql type using Modelarium';

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
        if (!is_string($name)) {
            $this->error('Invalid name parameter');
            return;
        }
        $this->setBaseName($name);

        $graphql = <<<EOF
extend type Query {
    {$this->lowerNamePlural}: [{$this->studlyName}!]! @paginate(defaultCount: 10)
    {$this->lowerName}(id: ID @eq): {$this->studlyName} @find
}

extend type Mutation {
    create{$this->studlyName}(input: Create{$this->studlyName}Input! @spread): {$this->studlyName}! @create
    delete{$this->studlyName}(id: ID!): {$this->studlyName} @delete
}

input Create{$this->studlyName}Input {
}

type {$this->studlyName} {
    id: ID!
    can: [Can!]
}
EOF;
        $target = base_path('graphql/data/' . $this->lowerName . '.graphql');
        $this->writeFile(
            $target,
            (bool)$this->option('overwrite'),
            $graphql
        );
        $this->info('Type generated at ' . $target);
    }
}
