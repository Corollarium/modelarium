<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use GraphQL\Language\AST\DirectiveNode;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;

class CastsDirective implements ModelDirectiveInterface
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
        foreach ($directive->arguments as $arg) {
            /**
             * @var \GraphQL\Language\AST\ArgumentNode $arg
             */
            /** @phpstan-ignore-next-line */
            $value = $arg->value->value;

            switch ($arg->name->value) {
            case 'type':
                $generator->casts[$fieldName] = $value;
            }
        }
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        \Formularium\Datatype $datatype = null
    ): ?\Formularium\Datatype {
        return null;
    }
}
