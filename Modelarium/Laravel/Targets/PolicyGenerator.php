<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Modelarium\BaseGenerator;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Parser;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class PolicyGenerator extends BaseGenerator
{
    /**
     * @var string
     */
    protected $stubDir = __DIR__ . "/stubs/";

    /**
     * @var ObjectType
     */
    protected $type = null;

    /**
     * @var GeneratedCollection
     */
    protected $collection = null;

    /**
     *
     * @var ClassType[]
     */
    protected $policyClasses = [];

    /**
     * @param Parser $parser
     * @param string $name
     * @param Type|string $type
     * @phpstan-ignore-next-line
     */
    public function __construct(Parser $parser, string $name, $type = null)
    {
        parent::__construct($parser, '', $type);
        $this->collection = new GeneratedCollection();
    }

    public function generate(): GeneratedCollection
    {
        foreach ($this->type->getFields() as $field) {
            $directives = $field->astNode->directives;
            $this->processFieldDirectives($field, $directives, 'Policy');
        }

        $printer = new \Nette\PhpGenerator\PsrPrinter;

        foreach ($this->policyClasses as $name => $c) {
            $namespace = new PhpNamespace('App\\Policies');
            $namespace->addUse('App\\Models\\User');
            $namespace->addUse('App\\Models\\' . $name);
            $namespace->add($c);

            $this->collection->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_POLICY,
                    "<?php declare(strict_types=1);\n\n" . $printer->printNamespace($namespace),
                    $this->getGenerateFilename($name),
                    true
                )
            );
        }
        return $this->collection;
    }

    public function getPolicyClass(string $name): ClassType
    {
        if (array_key_exists($name, $this->policyClasses)) {
            return $this->policyClasses[$name];
        }

        /**
         * @var ClassType $class
         */
        $class = new ClassType($name . 'Policy');
        $class
            ->addComment("This file was automatically generated by Modelarium.")
            ->setTraits(['Illuminate\Auth\Access\HandlesAuthorization']);
        $this->policyClasses[$name] = $class;
        return $class;
    }

    public function getGenerateFilename(string $name): string
    {
        return $this->getBasePath('app/Policies/'. Str::studly($name) . 'Policy.php');
    }
}
