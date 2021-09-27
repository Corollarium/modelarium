<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

class ModelariumModelCommand extends ModelariumTypeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:model
        {name : The model name.}
        {--overwrite : overwrite files if they exist}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a Graphql type using Modelarium. Alias for modelarium:type';
}
