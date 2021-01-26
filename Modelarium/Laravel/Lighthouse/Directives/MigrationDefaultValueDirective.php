<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class MigrationDefaultValueDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Default value for database column
"""
directive @migrationDefaultValue(
    """
    The value
    """
    value: String!
) on FIELD_DEFINITION
SDL;
    }
}
