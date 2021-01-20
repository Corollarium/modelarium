<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use GraphQL\Language\AST\DirectiveNode;
use Modelarium\Laravel\Targets\PolicyGenerator;
use Modelarium\Laravel\Targets\Interfaces\PolicyDirectiveInterface;
use Modelarium\Parser;

class CanDirective implements PolicyDirectiveInterface
{
    public static function processPolicyTypeDirective(
        PolicyGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }

    public static function processPolicyFieldDirective(
        PolicyGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $ability = '';
        $find = '';
        $injected = false;
        $args = false;

        list($type, $isRequired) = Parser::getUnwrappedType($field->type);

        /**
         * @var DirectiveNode $directive
         */
        $modelName = $type->name;

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
                    $modelName = $arg->value->value;
                break;
                case 'injectArgs':
                    $injected = true;
                break;
                case 'args':
                    $args = true;
                break;
            }
        }

        list($namespace, $modelClassName, $relativePath) = $generator->splitClassName($modelName);

        $class = $generator->getPolicyClass($modelClassName);

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
