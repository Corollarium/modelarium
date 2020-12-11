<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Type\Definition\NonNull;
use Modelarium\Laravel\Targets\PolicyGenerator;
use Modelarium\Laravel\Targets\Interfaces\PolicyDirectiveInterface;

class CanDirective implements PolicyDirectiveInterface
{
    public static function processPolicyFieldDirective(
        PolicyGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $ability = '';
        $find = '';
        $injected = false;
        $args = false;

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        /**
         * @var DirectiveNode $directive
         */

        $model = $type->name; /** @phpstan-ignore-line */
        
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

        list($namespace, $modelClassName, $relativePath) = $generator->splitClassName($model);

        $class = $generator->getClass($modelClassName);

        $method = $class->addMethod($ability);
        $method->setPublic()
            ->setReturnType('bool')
            ->addBody(
                'return false;'
            );
        $method->addParameter('user')->setType('\\App\\Models\\User')->setNullable(true);

        if ($find) {
            $method->addParameter('model')->setType('\\App\\Models\\' . $modelClassName);
        }
        if ($injected) {
            $method->addParameter('injectedArgs')->setType('array');
        }
        if ($args) {
            $method->addParameter('staticArgs')->setType('array');
        }
    }
}
