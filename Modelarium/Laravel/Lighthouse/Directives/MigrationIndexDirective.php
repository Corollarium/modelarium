<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class MigrationIndexDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Generates a composed index on the database for a type
"""
directive @index(
    """
    The list of fields to compose in the index
    """
    fields: [String]!
) on OBJECT
SDL;
    }
}
