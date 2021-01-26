<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class MigrationFulltextIndexDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Add a full text index to table
"""
directive @migrationFulltextIndex(
    """
    The fields to index. Must be an array even if it is just one field.
    """
    fields: [String!]!
) on OBJECT
SDL;
    }
}
