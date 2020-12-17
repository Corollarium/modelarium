<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Exception\Exception;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\MigrationCodeFragment;

class MigrationIndexDirective implements MigrationDirectiveInterface
{
    public static function processMigrationTypeDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        /** @phpstan-ignore-next-line */
        $values = $directive->arguments[0]->value->values;

        $indexFields = [];
        foreach ($values as $value) {
            $indexFields[] = $value->value;
        }
        if (!count($indexFields)) {
            throw new Exception("You must provide at least one field to an index");
        }
        $generator->createCode[] ='$table->index("' . implode('", "', $indexFields) .'");';
    }

    public static function processMigrationFieldDirective(
        MigrationGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        MigrationCodeFragment $code
    ): void {
        $code->appendExtraLine('$table->index("' . $field->name . '");');
    }

    public static function processMigrationRelationshipDirective(
        MigrationGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        MigrationCodeFragment $code
    ): void {
        $code->appendExtraLine('$table->index("' . $field->name . '");');
    }
}
