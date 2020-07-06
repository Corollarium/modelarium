<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Str;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Parser;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class PolicyGenerator extends BaseGenerator
{
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
            $this->processDirectives($field, $directives);
        }


        $printer = new \Nette\PhpGenerator\PsrPrinter;

        foreach ($this->policyClasses as $name => $c) {
            $namespace = new PhpNamespace('App\\Policies');
            $namespace->addUse('App\\User');
            $namespace->addUse('App\\' . $name);
            $namespace->add($c);

            $this->collection->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_POLICY,
                    "<?php declare(strict_types=1);\n\n" . $printer->printNamespace($namespace),
                    $this->getGenerateFilename($name)
                )
            );
        }
        return $this->collection;
    }

    public function processDirectives(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'can':
                $this->processCan($field, $directive);
            break;
            default:
            break;
            }
        }
    }

    public function processCan(
        \GraphQL\Type\Definition\FieldDefinition $field,
        DirectiveNode $directive
    ): void {
        $ability = '';
        $find = '';
        $injected = false;
        $args = false;

        if ($field->type instanceof NonNull) {
            $isRequired = true;
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        $model = $type->name; /** @phpstan-ignore-line */;
        
        foreach ($directive->arguments as $arg) {
            switch ($arg->name->value) {
                case 'ability':
                    // @phpstan-ignore-next-line
                    $ability = $arg->value->value;
                break;
                case 'find':
                    // @phpstan-ignore-next-line
                    $find = $arg->value->value;
                break;
                case 'model':
                    // @phpstan-ignore-next-line
                    $model = $arg->value->value;
                break;
                case 'injectArgs':
                    $injected = true;
                break;
                case 'args':
                    $args = true;
                break;
            }
        }

        list($namespace, $modelClassName, $relativePath) = $this->splitClassName($model);

        $class = $this->getClass($modelClassName);

        $method = $class->addMethod($ability);
        $method->setPublic()
            ->setReturnType('bool')
            ->addBody(
                'return false;'
            );
        $method->addParameter('user')->setType('\\App\\User');

        if ($find) {
            $method->addParameter('model')->setType('\\App\\' . $modelClassName);
        }
        if ($injected) {
            $method->addParameter('injectedArgs')->setType('array');
        }
        if ($args) {
            $method->addParameter('staticArgs')->setType('array');
        }
    }

    protected function getClass(string $name): ClassType
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
