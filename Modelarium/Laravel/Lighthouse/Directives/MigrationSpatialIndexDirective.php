<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class MigrationSpatialIndexDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Generates a migrationSpatialIndex
"""
directive @migrationSpatialIndex(
    """
    The field for the index
    """
    field: String!
) on OBJECT
SDL;
    }
}
