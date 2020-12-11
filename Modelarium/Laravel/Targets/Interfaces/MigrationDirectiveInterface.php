<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets\Interfaces;

use GraphQL\Type\Definition\Directive;
use Modelarium\Laravel\Targets\MigrationGenerator;

interface MigrationDirectiveInterface
{
    public static function processMigrationTypeDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void;

    public static function processMigrationFieldDirective(
        MigrationGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void;
}
