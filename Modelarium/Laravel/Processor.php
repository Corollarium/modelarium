<?php declare(strict_types=1);

namespace Modelarium\Laravel;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use HaydenPierce\ClassFinder\ClassFinder;
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
use Nette\PhpGenerator\ClassType;
use Nuwave\Lighthouse\Schema\Factories\DirectiveFactory;

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
     * DatabaseSeeder class for Laravel
     *
     * @var ClassType
     */
    protected $seederClass = null;

    public function __construct()
    {
        $this->parser = new Parser();
        $this->parser->setImport('directives.graphql', self::getDirectivesGraphqlString());
    }

    /**
     * Scan the given namespaces for directive classes.
     *
     * @param  string[]  $directiveNamespaces
     * @return array<string, string>
     */
    public static function getDirectivesGraphql($directiveNamespaces = [ 'Modelarium\Laravel\Lighthouse\Directives' ]): array
    {
        $directives = [];

        foreach ($directiveNamespaces as $directiveNamespace) {
            /** @var array<class-string> $classesInNamespace */
            $classesInNamespace = ClassFinder::getClassesInNamespace($directiveNamespace);

            foreach ($classesInNamespace as $class) {
                $reflection = new \ReflectionClass($class);
                if (! $reflection->isInstantiable()) {
                    continue;
                }

                if (! is_a($class, \Nuwave\Lighthouse\Schema\Directives\BaseDirective::class, true)) {
                    continue;
                }

                $name = DirectiveFactory::directiveName((string)$class);
                $directives[$name] = trim($class::definition());
            }
        }

        return $directives;
    }

    /**
     * Scan the given namespaces for directive classes.
     *
     * @param  string[]  $directiveNamespaces
     * @return string
     */
    public static function getDirectivesGraphqlString($directiveNamespaces = [ 'Modelarium\\Laravel\\Lighthouse\\Directives' ]): string
    {
        return implode("\n\n", self::getDirectivesGraphql($directiveNamespaces));
    }

    /**
     *
     * @param string[] $files
     * @return GeneratedCollection
     */
    public function processFiles(array $files): GeneratedCollection
    {
        $this->parser->fromFiles($files);
        return $this->process();
    }

    /**
     *
     * @param string $data
     * @return GeneratedCollection
     */
    public function processString(string $data): GeneratedCollection
    {
        $this->parser->fromString($data);
        return $this->process();
    }

    /**
     *
     * @param string[] $data
     * @return GeneratedCollection
     */
    public function processStrings(array $data): GeneratedCollection
    {
        $this->parser->fromStrings($data);
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
        if ($this->runSeed) {
            $this->createSeederClass();
        }

        foreach ($typeMap as $name => $object) {
            if ($object instanceof ObjectType) {
                if ($name === 'Query') {
                    continue;
                } elseif ($name === 'Mutation') {
                    continue;
                } elseif ($name === 'Subscription') {
                    continue;
                }
                $g = $this->processType((string)$name, $object);
                $this->collection = $this->collection->merge($g);
            }
        }

        $this->collection = $this->collection->merge($this->processMutation($schema->getMutationType()));

        if ($this->runSeed) {
            $printer = new \Nette\PhpGenerator\PsrPrinter;
            $seeder = "<?php\n\n" . $printer->printClass($this->seederClass);
            $this->collection->add(
                new GeneratedItem(
                    GeneratedItem::TYPE_SEED,
                    $seeder,
                    SeedGenerator::getBasePath('database/seeds/DatabaseSeeder.php')
                )
            );
        }

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
            $generator = new SeedGenerator($this->parser, $name, $object);
            $collection = $collection->merge($generator->generate());

            $this->seederClass->getMethod('run')
                ->addBody('$this->call(' . $generator->getStudlyName() . 'Seeder::class);');
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
            $collection = $collection->merge((new PolicyGenerator($this->parser, 'Mutation', $object))->generate());
        }
        if ($this->runEvent) {
            $collection = $collection->merge((new EventGenerator($this->parser, 'Mutation', $object))->generate());
        }
        return $collection;
    }

    /**
     * Generates the DatabaseSeeder class.
     *
     * @return void
     */
    protected function createSeederClass(): void
    {
        $this->seederClass = new \Nette\PhpGenerator\ClassType('DatabaseSeeder');
        $this->seederClass->setExtends('Illuminate\Database\Seeder')
            ->addComment("This file was automatically generated by Modelarium.");

        $this->seederClass->addMethod('run')
                ->setPublic()
                ->addComment("Seed the application\'s database.\n@return void");
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
