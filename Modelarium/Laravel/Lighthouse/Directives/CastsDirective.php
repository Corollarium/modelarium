<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class CastsDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Casts type
"""
directive @casts(
    """
    The value
    """
    type: String!
) on FIELD_DEFINITION
SDL;
    }
}
