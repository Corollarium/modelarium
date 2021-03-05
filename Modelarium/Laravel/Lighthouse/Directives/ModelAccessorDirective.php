<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class ModelAccessorDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Creates an accessor method in the class.
"""
directive @modelAccessor on FIELD_DEFINITION
SDL;
    }
}
