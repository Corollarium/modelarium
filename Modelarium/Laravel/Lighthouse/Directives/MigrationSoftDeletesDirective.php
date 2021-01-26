<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class MigrationSoftDeletesDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Specifies a soft delete mode for this type
"""
directive @migrationSoftDeletes on OBJECT
SDL;
    }
}
