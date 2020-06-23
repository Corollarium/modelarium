<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Type\Definition\ObjectType;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class PolicyGenerator extends BaseGenerator
{
    /**
     * @var ObjectType
     */
    protected $type = null;

    /**
     *
     * @var string
     */
    protected $modelName = '';

    /**
     * @var array
     */
    protected $policies = [];


    public function generate(): GeneratedCollection
    {
        return new GeneratedCollection(
            [ new GeneratedItem(
                GeneratedItem::TYPE_POLICY,
                $this->generateString(),
                $this->getGenerateFilename()
            )]
        );
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
        $model = $field->type->name; /** @phpstan-ignore-line */;
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
                    $injected = ', array $injectedArgs';
                break;
                case 'args':
                    $args = ', array $staticArgs';
                break;
            }
        }

        if (!$this->modelName) {
            $this->modelName = $model;
        } else {
            if ($model != $this->modelName) {
                // TODO
                throw new Exception(
                    'Mixing fields with different arguments in the same mutation type is not supported yet.'
                );
            }
        }
        if ($find) {
            $stub = <<<EOF

    /**
     * 
     *
     * @param \App\User \$user
     * @param \App\{$model} \${$this->lowerName}
     * @return mixed
     */
    public function {$ability}(User \$user, {$model} \${$this->lowerName}): bool
    {
        return false; // TODO: fill with your logic
    }
EOF;
        } elseif ($args || $injected) {
            $stub = <<<EOF

    /**
     * 
     * @param \App\User  \$user
     * @param array      \$args
     * @return bool
     */
    public function {$ability}(User \$user$injected$args): bool
    {
        return false; // TODO: fill with your logic
    }
EOF;
        } else {
            $stub = <<<EOF

    /**
     * 
     * @param \App\User  \$user
     * @return bool
     */
    public function {$ability}(User \$user): bool
    {
        return false; // TODO: fill with your logic
    }
EOF;
        }

        $this->policies[] = $stub;
    }

    public function generateString(): string
    {
        return $this->stubToString('policy', function ($stub) {
            foreach ($this->type->getFields() as $field) {
                $directives = $field->astNode->directives;
                $this->processDirectives($field, $directives);
            }

            $stub = str_replace(
                'DummyPolicyClassName',
                $this->modelName,
                $stub
            );
            
            $stub = str_replace(
                '{{dummyCode}}',
                join("\n", $this->policies),
                $stub
            );
            return $stub;
        });
    }

    public function getGenerateFilename(): string
    {
        return $this->getBasePath('app/Policies/'. $this->studlyName . 'Policy.php');
    }
}
