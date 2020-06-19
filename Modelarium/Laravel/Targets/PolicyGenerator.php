<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class PolicyGenerator extends BaseGenerator
{
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
    ) {
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
     * @param \App\{$this->model} \${$this->lowerName}
     * @return mixed
     */
    public function {$ability}(User \$user, {$model} \${$this->lowerName}):bool
    {
        return false; // TODO
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
        return false; // TODO
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
        return false; // TODO
    }
EOF;
                }
                break;
            default:
            break;
            }
        }
    }

    public function generateString(): string
    {
        return $this->stubToString('policy', function ($stub) {
            $this->processDirectives($this->type->astNode->directives);

            $stub = str_replace(
                '{{dummyCode}}',
                join("\n", $this->code),
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
