<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class ModelExtendsDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Base class to extend on model.
"""
directive @modelExtends(
    """
    The base class name with namespace
    """
    class: String!
) on OBJECT
SDL;
    }
}
