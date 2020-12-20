<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class EagerLoadDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Eager load relationships using Laravel Model::$with();
"""
directive @eagerLoad (
    """
    If present, use this name as the with($name) parameter. Otherwise try to guess from field.
    """
    name: String

    """
    If present, append these values to the with($name) model class.
    """
    tables: [String!] 
) on FIELD_DEFINITION | OBJECT
SDL;
    }
}
