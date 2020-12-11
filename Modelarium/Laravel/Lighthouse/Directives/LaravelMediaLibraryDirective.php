<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class LaravelMediaLibraryDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Implement the Laravel Media Library on this model, adding interface/traits.
"""
directive @laravelMediaLibrary on OBJECT
SDL;
    }
}
