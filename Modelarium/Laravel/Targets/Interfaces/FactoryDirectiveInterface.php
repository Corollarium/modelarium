<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets\Interfaces;

use Modelarium\Laravel\Targets\FactoryGenerator;

interface FactoryDirectiveInterface
{
    public static function processFactoryTypeDirective(
        FactoryGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void;

    public static function processFactoryFieldDirective(
        FactoryGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void;
}
