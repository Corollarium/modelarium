<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;

class RenderableDirective implements ModelDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        foreach ($directive->arguments as $arg) {
            /**
             * @var \GraphQL\Language\AST\ArgumentNode $arg
             */

            $argName = $arg->name->value;
            $argValue = $arg->value->value; /** @phpstan-ignore-line */
            $generator->fModel->appendRenderable($argName, $argValue);
        }
    }

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
       \Formularium\Field $fieldFormularium,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        // handled by FormulariumUtils::getFieldFromDirectives()
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): string {
        return '';
    }
}
