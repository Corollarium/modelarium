<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets\Interfaces;

use Modelarium\Laravel\Targets\PolicyGenerator;

interface PolicyDirectiveInterface
{
    public static function processPolicyFieldDirective(
        PolicyGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\Node $directive
    ): void;
}
