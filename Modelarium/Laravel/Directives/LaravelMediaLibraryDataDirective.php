<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;

class LaravelMediaLibraryDataDirective implements ModelDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\Node $directive
    ): void {
    }

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\Node $directive
    ): void {
    }

    public function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\Node $directive
    ): void {
    }
}
