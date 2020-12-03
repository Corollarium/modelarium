<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class RenderableDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Generate renderable
"""
directive @renderable on FIELD_DEFINITION | OBJECT
SDL;
    }
}
