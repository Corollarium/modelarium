<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Parser;
use Modelarium\Processor as ModelariumProcessor;

class Processor extends ModelariumProcessor
{
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
                if ($g) {
                    $data = $data->merge($g);
                }
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

        $gen = new MigrationGenerator($this->parser, $name, $object);
        return $gen->generate();
    }

    protected function processMutation(?Type $object):  GeneratedCollection
    {
        return new GeneratedCollection();
    }
}
