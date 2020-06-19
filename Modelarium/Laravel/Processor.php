<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Laravel\Targets\FactoryGenerator;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\SeedGenerator;
use Modelarium\Parser;
use Modelarium\Processor as ModelariumProcessor;

class Processor extends ModelariumProcessor
{
    /**
     * @var Parser
     */
    protected $parser = null;

    /**
     *
     * @param string $data
     * @return GeneratedCollection
     */
    public function processString(string $data): GeneratedCollection
    {
        $this->parser = Parser::fromString($data);
        $schema = $this->parser->getSchema();
        $typeMap = $schema->getTypeMap();

        $data = new GeneratedCollection();
        foreach ($typeMap as $name => $object) {
            if ($object instanceof ObjectType) {
                $g = $this->processType($name, $object);
                $data = $data->merge($g);
            }
        }

        // TODO $this->processMutation($schema->getMutationType());
        return $data;
    }

    protected function processType(string $name, ObjectType $object): GeneratedCollection
    {
        if (str_starts_with($name, '__')) {
            // internal type
            return new GeneratedCollection();
        }

        $collection = (new MigrationGenerator($this->parser, $name, $object))->generate();
        $collection = $collection->merge((new SeedGenerator($this->parser, $name, $object))->generate());
        $collection = $collection->merge((new FactoryGenerator($this->parser, $name, $object))->generate());
        $collection = $collection->merge((new ModelGenerator($this->parser, $name, $object))->generate());
        return $collection;
    }

    protected function processMutation(?Type $object):  GeneratedCollection
    {
        return new GeneratedCollection();
    }
}
