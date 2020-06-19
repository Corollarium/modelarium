<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\ObjectType;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class PolicyGenerator extends BaseGenerator
{
    /**
     * @var ObjectType
     */
    protected $type = null;

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
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'can':
                $ability = '';
                $find = '';
                $injected = false;
                $args = false;
                $model = $this->studlyName;
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */
                    $value = $arg->value->value;
                    switch ($arg->name->value) {
                        case 'ability':
                            $ability = $value;
                        break;
                        case 'find':
                            $find = $value;
                        break;
                        case 'model':
                            $model = $value;
                        break;
                        case 'injectArgs':
                            $injected = ', array $injectedArgs';
                        break;
                        case 'args':
                            $args = ', array $staticArgs';
                        break;
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
    public function {$ability}(User \$user, {$model} \${$this->lowerName}):bool
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
    public function {$ability}(User \$user $injected $args):bool
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
    public function {$ability}(User \$user):bool
    {
        return false; // TODO: fill with your logic
    }
EOF;
                }
                $this->policies[] = $stub;
                break;
            default:
            break;
            }
        }
    }

    public function generateString(): string
    {
        return $this->stubToString('policy', function ($stub) {
            /**
             * @var \GraphQL\Language\AST\NodeList|null
             */
            $directives = $this->type->astNode->directives;
            if ($directives) {
                $this->processDirectives($directives);
            }

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
