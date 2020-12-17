<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Illuminate\Support\Str;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\MigrationCodeFragment;
use Modelarium\Laravel\Targets\MigrationGenerator;

class MigrationUniqueIndexDirective implements ModelDirectiveInterface, MigrationDirectiveInterface
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
        $studlyName = $generator->getStudlyName();
        $generator->class->addMethod('from' . Str::studly($fieldName))
            ->setPublic()
            ->setStatic()
            ->setReturnType('\\App\\Models\\' . $studlyName)
            ->setReturnNullable()
            ->addComment("Factory from the $fieldName unique index")
            ->setBody("return {$studlyName}::firstWhere('$fieldName', \$value);")
            ->addParameter('value');
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): string {
        return '';
    }

    public static function processMigrationTypeDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }

    public static function processMigrationFieldDirective(
        MigrationGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        MigrationCodeFragment $code
    ): void {
        $code->appendExtraLine('$table->unique("' . $field->name . '");');
    }
}
