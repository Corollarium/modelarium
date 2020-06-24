<?php declare(strict_types=1);

namespace Modelarium;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\AST;
use Modelarium\Laravel\Targets\MigrationGenerator;

abstract class Processor
{
    /**
     *
     * @var GeneratedCollection
     */
    protected $collection = null;

    /**
     *
     * @param string $data
     * @return GeneratedCollection
     */
    abstract public function processString(string $data): GeneratedCollection;
    
    /**
     * Get the value of generated data
     *
     * @return GeneratedCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
