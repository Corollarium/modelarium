<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class MigrationIndexDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Generates a composed index on the database for a type
"""
directive @migrationIndex(
    """
    The list of fields to compose in the index
    """
    fields: [String!]!
) on OBJECT
SDL;
    }
}
