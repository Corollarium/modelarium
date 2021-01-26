<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class ModelSkipDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Do not generate model (and seeder/factory/etc) for this type
"""
directive @modelSkip on OBJECT
SDL;
    }
}
