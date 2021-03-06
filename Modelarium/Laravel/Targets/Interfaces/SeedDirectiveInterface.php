<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets\Interfaces;

use Modelarium\Laravel\Targets\SeedGenerator;

interface SeedDirectiveInterface
{
    public static function processSeedTypeDirective(
        SeedGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void;

    public static function processSeedFieldDirective(
        SeedGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void;
}
