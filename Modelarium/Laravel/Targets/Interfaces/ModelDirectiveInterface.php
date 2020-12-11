<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets\Interfaces;

use Modelarium\Laravel\Targets\ModelGenerator;

interface ModelDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\Node $directive
    ): void;

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\Node $directive
    ): void;

    public function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\Node $directive
    ): void;
}
