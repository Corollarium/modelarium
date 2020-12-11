<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Exception\Exception;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;

class MigrationFulltextIndexDirective implements MigrationDirectiveInterface, ModelDirectiveInterface
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
            throw new Exception("You must provide at least one field to a full text index");
        }
        $generator->postCreateCode[] = "DB::statement('ALTER TABLE " .
            $generator->getLowerNamePlural() .
            " ADD FULLTEXT fulltext_index (\"" .
            implode('", "', $indexFields) .
            "\")');";
    }

    public static function processMigrationFieldDirective(
        MigrationGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        // nothing
    }

    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        throw new Exception("Primary index is not implemented yet");
    }

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        // nothing
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): string {
        // nothing
        return '';
    }
}
