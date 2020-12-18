<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class MigrationAlterTableDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Alters a table on migration after it was created.
"""
directive @migrationAlterTable(
    """
    The commands to run, which will be prepended with 'ALTER TABLE tablename"
    """
    values: [String!]!
) on OBJECT
SDL;
    }
}
