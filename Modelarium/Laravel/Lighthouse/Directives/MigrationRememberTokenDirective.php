<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class MigrationRememberTokenDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Specifies that remember me tokens should be created on DB.
"""
directive @migrationRememberToken on OBJECT
SDL;
    }
}
