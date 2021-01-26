<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class MorphedByManyDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Handles the target of morphMany
"""
directive @morphedByMany on FIELD_DEFINITION
SDL;
    }
}
