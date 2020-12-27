<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class MigrationUniqueIndexDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Generates a unique index on the database for that field
"""
directive @migrationUniqueIndex (
    """
    The list of fields to compose in the index
    """
    fields: [String!]
) on FIELD_DEFINITION | OBJECT
SDL;
    }
}
