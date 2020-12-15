<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;

class ModelFillableDirective implements ModelDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
       \Formularium\Field $fieldFormularium,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $fieldName = $field->name;
        $generator->fillable[] = $fieldName;
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): string {
        return '';
    }
}
