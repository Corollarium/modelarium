<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Laravel\Targets\EventGenerator;
use Modelarium\Laravel\Targets\FactoryGenerator;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\PolicyGenerator;
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
     * @var bool
     */
    protected $runMigration = true;

    /**
     * @var bool
     */
    protected $runSeed = true;

    /**
     * @var bool
     */
    protected $runFactory = true;

    /**
     * @var bool
     */
    protected $runModel = true;

    /**
     * @var bool
     */
    protected $runPolicy = true;

    /**
     * @var bool
     */
    protected $runEvent = true;

    /**
     * Returns directives defined by Modelarium.
     *
     * @return string
     * @throws \Safe\Exceptions\FilesystemException
     */
    public static function getDirectives(): string
    {
        return \Safe\file_get_contents(__DIR__ . '/Graphql/definitions.graphql');
    }

    /**
     *
     * @param string[] $files
     * @return GeneratedCollection
     */
    public function processFiles(array $files): GeneratedCollection
    {
        $this->parser = Parser::fromFiles($files);
        return $this->process();
    }

    /**
     *
     * @param string $data
     * @return GeneratedCollection
     */
    public function processString(string $data): GeneratedCollection
    {
        $this->parser = Parser::fromString($data);
        return $this->process();
    }

    /**
     *
     * @param string[] $data
     * @return GeneratedCollection
     */
    public function processStrings(array $data): GeneratedCollection
    {
        $this->parser = Parser::fromStrings($data);
        return $this->process();
    }

    /**
     *
     * @return GeneratedCollection
     */
    public function process(): GeneratedCollection
    {
        $schema = $this->parser->getSchema();
        $typeMap = $schema->getTypeMap();

        $this->collection = new GeneratedCollection();
        foreach ($typeMap as $name => $object) {
            if ($object instanceof ObjectType) {
                if ($name === 'Query') {
                    continue;
                }
                if ($name === 'Mutation') {
                    continue;
                }
                $g = $this->processType((string)$name, $object);
                $this->collection = $this->collection->merge($g);
            }
        }

        $this->collection->merge($this->processMutation($schema->getMutationType()));

        return $this->collection;
    }

    protected function processType(string $name, ObjectType $object): GeneratedCollection
    {
        $collection = new GeneratedCollection();
        if (str_starts_with($name, '__')) {
            // internal type
            return $collection;
        }

        if ($this->runMigration) {
            $collection = $collection->merge((new MigrationGenerator($this->parser, $name, $object))->generate());
        }
        if ($this->runSeed) {
            $collection = $collection->merge((new SeedGenerator($this->parser, $name, $object))->generate());
        }
        if ($this->runFactory) {
            $collection = $collection->merge((new FactoryGenerator($this->parser, $name, $object))->generate());
        }
        if ($this->runModel) {
            $collection = $collection->merge((new ModelGenerator($this->parser, $name, $object))->generate());
        }
        return $collection;
    }

    protected function processMutation(?Type $object):  GeneratedCollection
    {
        $collection = new GeneratedCollection();
        if (!$object) {
            return $collection;
        }
        if ($this->runPolicy) {
            $collection = (new PolicyGenerator($this->parser, 'Mutation', $object))->generate();
        }
        if ($this->runEvent) {
            $collection = (new EventGenerator($this->parser, 'Mutation', $object))->generate();
        }
        return $collection;
    }

    /**
     * Set the value of runMigration
     *
     * @param  bool  $runMigration
     *
     * @return  self
     */
    public function setRunMigration(bool $runMigration): self
    {
        $this->runMigration = $runMigration;

        return $this;
    }

    /**
     * Set the value of runSeed
     *
     * @param  bool  $runSeed
     *
     * @return  self
     */
    public function setRunSeed(bool $runSeed): self
    {
        $this->runSeed = $runSeed;

        return $this;
    }

    /**
     * Set the value of runFactory
     *
     * @param  bool  $runFactory
     *
     * @return  self
     */
    public function setRunFactory(bool $runFactory): self
    {
        $this->runFactory = $runFactory;

        return $this;
    }

    /**
     * Set the value of runModel
     *
     * @param  bool  $runModel
     *
     * @return  self
     */
    public function setRunModel(bool $runModel): self
    {
        $this->runModel = $runModel;

        return $this;
    }

    /**
     * Set the value of runPolicy
     *
     * @param  bool  $runPolicy
     *
     * @return  self
     */
    public function setRunPolicy(bool $runPolicy): self
    {
        $this->runPolicy = $runPolicy;

        return $this;
    }

    /**
     * Set the value of runEvent
     *
     * @param  bool  $runEvent
     *
     * @return  self
     */
    public function setRunEvent(bool $runEvent): self
    {
        $this->runEvent = $runEvent;

        return $this;
    }
}