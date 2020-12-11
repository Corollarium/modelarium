<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Exception\Exception;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;

class MigrationSpatialIndexDirective implements MigrationDirectiveInterface, ModelDirectiveInterface
{
    public static function processMigrationTypeDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        /** @phpstan-ignore-next-line */
        $generator->createCode[] = '$table->spatialIndex("' . $directive->arguments[0]->value->value .'");';
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