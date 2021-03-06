<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class TypeSkipDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Do not process this type in Modelarium. Will not create models, migrations, etc
"""
directive @typeSkip on OBJECT | ENUM
SDL;
    }
}
