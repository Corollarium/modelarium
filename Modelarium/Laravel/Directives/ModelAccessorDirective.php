<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Formularium\Datatype\Datatype_constant;
use Illuminate\Support\Str;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Parser;

class ModelAccessorDirective implements ModelDirectiveInterface
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
        $studly = Str::studly($field->name);
        // TODO: return type, converted to PHP
        // list($type, $isRequired) = Parser::getUnwrappedType($field->getType());
        // $typeName = $type->name;

        $generator->class->addMethod("get{$studly}Attribute")
            ->setPublic()
            ->setAbstract(); // user will fill it
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        \Formularium\Datatype $datatype = null
    ): ?\Formularium\Datatype {
        $studly = Str::studly($field->name);
        list($type, $isRequired) = Parser::getUnwrappedType($field->getType());

        $generator->class->addMethod("get{$studly}Attribute")
            ->setPublic()
            ->setReturnType('array')
            ->setReturnNullable($isRequired)
            ->setAbstract();
        return new Datatype_constant();
    }
}
